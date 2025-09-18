const axios = require('axios');
const { executeQuery, executeTransaction } = require('./database');

class IMDbService {
  constructor() {
    this.imdbApiKey = process.env.IMDB_API_KEY;
    this.tmdbApiKey = process.env.TMDB_API_KEY;
    this.baseUrl = 'https://api.themoviedb.org/3';
  }

  // Search for movies/TV shows
  async search(query, type = 'multi') {
    try {
      const response = await axios.get(`${this.baseUrl}/search/${type}`, {
        params: {
          api_key: this.tmdbApiKey,
          query: query,
          include_adult: false
        }
      });

      return response.data.results;
    } catch (error) {
      console.error('TMDb search error:', error);
      throw new Error('Failed to search TMDb');
    }
  }

  // Get movie details
  async getMovieDetails(tmdbId) {
    try {
      const [detailsResponse, creditsResponse] = await Promise.all([
        axios.get(`${this.baseUrl}/movie/${tmdbId}`, {
          params: { api_key: this.tmdbApiKey }
        }),
        axios.get(`${this.baseUrl}/movie/${tmdbId}/credits`, {
          params: { api_key: this.tmdbApiKey }
        })
      ]);

      const movie = detailsResponse.data;
      const credits = creditsResponse.data;

      return {
        title: movie.title,
        description: movie.overview,
        imdb_id: movie.imdb_id,
        tmdb_id: movie.id,
        release_date: movie.release_date,
        duration: movie.runtime,
        genre: movie.genres.map(g => g.name).join(', '),
        director: credits.crew.find(person => person.job === 'Director')?.name || '',
        cast: credits.cast.slice(0, 10).map(actor => actor.name).join(', '),
        rating: movie.vote_average,
        poster_url: movie.poster_path ? `https://image.tmdb.org/t/p/w500${movie.poster_path}` : null,
        backdrop_url: movie.backdrop_path ? `https://image.tmdb.org/t/p/w1280${movie.backdrop_path}` : null
      };
    } catch (error) {
      console.error('TMDb movie details error:', error);
      throw new Error('Failed to get movie details from TMDb');
    }
  }

  // Get TV show details
  async getTVShowDetails(tmdbId) {
    try {
      const [detailsResponse, creditsResponse] = await Promise.all([
        axios.get(`${this.baseUrl}/tv/${tmdbId}`, {
          params: { api_key: this.tmdbApiKey }
        }),
        axios.get(`${this.baseUrl}/tv/${tmdbId}/credits`, {
          params: { api_key: this.tmdbApiKey }
        })
      ]);

      const show = detailsResponse.data;
      const credits = creditsResponse.data;

      return {
        title: show.name,
        description: show.overview,
        tmdb_id: show.id,
        first_air_date: show.first_air_date,
        last_air_date: show.last_air_date,
        genre: show.genres.map(g => g.name).join(', '),
        creator: show.created_by.map(c => c.name).join(', '),
        cast: credits.cast.slice(0, 10).map(actor => actor.name).join(', '),
        rating: show.vote_average,
        poster_url: show.poster_path ? `https://image.tmdb.org/t/p/w500${show.poster_path}` : null,
        backdrop_url: show.backdrop_path ? `https://image.tmdb.org/t/p/w1280${show.backdrop_path}` : null
      };
    } catch (error) {
      console.error('TMDb TV show details error:', error);
      throw new Error('Failed to get TV show details from TMDb');
    }
  }

  // Get episodes for a TV show
  async getTVShowEpisodes(tmdbId, seasonNumber) {
    try {
      const response = await axios.get(`${this.baseUrl}/tv/${tmdbId}/season/${seasonNumber}`, {
        params: { api_key: this.tmdbApiKey }
      });

      const season = response.data;
      return season.episodes.map(episode => ({
        title: episode.name,
        description: episode.overview,
        season_number: seasonNumber,
        episode_number: episode.episode_number,
        air_date: episode.air_date,
        duration: episode.runtime,
        rating: episode.vote_average
      }));
    } catch (error) {
      console.error('TMDb episodes error:', error);
      throw new Error('Failed to get episodes from TMDb');
    }
  }

  // Import movie to database
  async importMovie(tmdbId, userId) {
    try {
      const movieData = await this.getMovieDetails(tmdbId);
      
      const result = await executeQuery(
        `INSERT INTO movies (
          title, description, imdb_id, tmdb_id, release_date, duration, 
          genre, director, cast, rating, poster_url, backdrop_url, 
          status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [
          movieData.title, movieData.description, movieData.imdb_id, movieData.tmdb_id,
          movieData.release_date, movieData.duration, movieData.genre, movieData.director,
          movieData.cast, movieData.rating, movieData.poster_url, movieData.backdrop_url,
          'draft', userId
        ]
      );

      return {
        id: result.insertId,
        ...movieData
      };
    } catch (error) {
      if (error.code === 'ER_DUP_ENTRY') {
        throw new Error('Movie already exists in database');
      }
      throw error;
    }
  }

  // Import TV show to database
  async importTVShow(tmdbId, userId, includeEpisodes = false) {
    return executeTransaction(async (connection) => {
      try {
        const showData = await this.getTVShowDetails(tmdbId);
        
        const [result] = await connection.execute(
          `INSERT INTO tv_shows (
            title, description, tmdb_id, first_air_date, last_air_date,
            genre, creator, cast, rating, poster_url, backdrop_url,
            status, created_by
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
          [
            showData.title, showData.description, showData.tmdb_id,
            showData.first_air_date, showData.last_air_date, showData.genre,
            showData.creator, showData.cast, showData.rating, showData.poster_url,
            showData.backdrop_url, 'draft', userId
          ]
        );

        const showId = result.insertId;
        const episodes = [];

        if (includeEpisodes) {
          // Get show details to know number of seasons
          const showDetails = await axios.get(`${this.baseUrl}/tv/${tmdbId}`, {
            params: { api_key: this.tmdbApiKey }
          });

          const numberOfSeasons = showDetails.data.number_of_seasons;

          // Import episodes for each season
          for (let season = 1; season <= numberOfSeasons; season++) {
            try {
              const seasonEpisodes = await this.getTVShowEpisodes(tmdbId, season);
              
              for (const episode of seasonEpisodes) {
                const [episodeResult] = await connection.execute(
                  `INSERT INTO episodes (
                    tv_show_id, title, description, season_number, episode_number,
                    air_date, duration, rating
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
                  [
                    showId, episode.title, episode.description, episode.season_number,
                    episode.episode_number, episode.air_date, episode.duration, episode.rating
                  ]
                );
                
                episodes.push({
                  id: episodeResult.insertId,
                  ...episode
                });
              }
            } catch (seasonError) {
              console.error(`Error importing season ${season}:`, seasonError);
              // Continue with other seasons
            }
          }
        }

        return {
          id: showId,
          ...showData,
          episodes
        };
      } catch (error) {
        if (error.code === 'ER_DUP_ENTRY') {
          throw new Error('TV show already exists in database');
        }
        throw error;
      }
    });
  }

  // Get popular movies
  async getPopularMovies(page = 1) {
    try {
      const response = await axios.get(`${this.baseUrl}/movie/popular`, {
        params: {
          api_key: this.tmdbApiKey,
          page: page
        }
      });

      return response.data;
    } catch (error) {
      console.error('TMDb popular movies error:', error);
      throw new Error('Failed to get popular movies from TMDb');
    }
  }

  // Get popular TV shows
  async getPopularTVShows(page = 1) {
    try {
      const response = await axios.get(`${this.baseUrl}/tv/popular`, {
        params: {
          api_key: this.tmdbApiKey,
          page: page
        }
      });

      return response.data;
    } catch (error) {
      console.error('TMDb popular TV shows error:', error);
      throw new Error('Failed to get popular TV shows from TMDb');
    }
  }

  // Batch import movies
  async batchImportMovies(movieIds, userId, jobId) {
    const results = {
      total: movieIds.length,
      successful: 0,
      failed: 0,
      errors: []
    };

    for (const tmdbId of movieIds) {
      try {
        await this.importMovie(tmdbId, userId);
        results.successful++;
        
        // Update job progress
        await executeQuery(
          'UPDATE import_jobs SET processed_items = ? WHERE id = ?',
          [results.successful + results.failed, jobId]
        );
      } catch (error) {
        results.failed++;
        results.errors.push({
          tmdbId,
          error: error.message
        });
        
        // Update job progress
        await executeQuery(
          'UPDATE import_jobs SET processed_items = ?, failed_items = ? WHERE id = ?',
          [results.successful + results.failed, results.failed, jobId]
        );
      }
    }

    return results;
  }

  // Batch import TV shows
  async batchImportTVShows(showIds, userId, jobId, includeEpisodes = false) {
    const results = {
      total: showIds.length,
      successful: 0,
      failed: 0,
      errors: []
    };

    for (const tmdbId of showIds) {
      try {
        await this.importTVShow(tmdbId, userId, includeEpisodes);
        results.successful++;
        
        // Update job progress
        await executeQuery(
          'UPDATE import_jobs SET processed_items = ? WHERE id = ?',
          [results.successful + results.failed, jobId]
        );
      } catch (error) {
        results.failed++;
        results.errors.push({
          tmdbId,
          error: error.message
        });
        
        // Update job progress
        await executeQuery(
          'UPDATE import_jobs SET processed_items = ?, failed_items = ? WHERE id = ?',
          [results.successful + results.failed, results.failed, jobId]
        );
      }
    }

    return results;
  }
}

module.exports = new IMDbService();