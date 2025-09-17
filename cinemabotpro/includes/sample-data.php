<?php
/**
 * Sample Data for CinemaBot Pro
 * This file contains sample movie and TV show data for demonstration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sample movie data
 */
function cinemabotpro_get_sample_movies() {
    return array(
        array(
            'title' => 'The Dark Knight',
            'year' => 2008,
            'type' => 'movie',
            'genres' => array('Action', 'Crime', 'Drama'),
            'rating' => 4.8,
            'duration' => '152 min',
            'language' => 'en',
            'description' => 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.',
            'director' => 'Christopher Nolan',
            'cast' => array('Christian Bale', 'Heath Ledger', 'Aaron Eckhart', 'Michael Caine'),
            'poster_url' => 'https://via.placeholder.com/300x450/000000/FFFFFF?text=The+Dark+Knight',
            'trailer_url' => 'https://www.youtube.com/embed/EXeTwQWrcwY'
        ),
        array(
            'title' => 'Dangal',
            'year' => 2016,
            'type' => 'movie',
            'genres' => array('Biography', 'Drama', 'Sport'),
            'rating' => 4.7,
            'duration' => '161 min',
            'language' => 'hi',
            'description' => 'Former wrestler Mahavir Singh Phogat and his two wrestler daughters struggle towards glory at the Commonwealth Games in the face of societal oppression.',
            'director' => 'Nitesh Tiwari',
            'cast' => array('Aamir Khan', 'Fatima Sana Shaikh', 'Sanya Malhotra', 'Sakshi Tanwar'),
            'poster_url' => 'https://via.placeholder.com/300x450/FF6B35/FFFFFF?text=Dangal',
            'trailer_url' => 'https://www.youtube.com/embed/x_7YlGv9u1g'
        ),
        array(
            'title' => 'Pather Panchali',
            'year' => 1955,
            'type' => 'movie',
            'genres' => array('Drama', 'Family'),
            'rating' => 4.6,
            'duration' => '125 min',
            'language' => 'bn',
            'description' => 'Impoverished priest Harihar Ray, dreaming of a better life for himself and his family, leaves his rural Bengal village in search of work.',
            'director' => 'Satyajit Ray',
            'cast' => array('Kanu Bannerjee', 'Karuna Bannerjee', 'Subir Banerjee', 'Uma Dasgupta'),
            'poster_url' => 'https://via.placeholder.com/300x450/2E8B57/FFFFFF?text=Pather+Panchali',
            'trailer_url' => null
        ),
        array(
            'title' => 'Parasite',
            'year' => 2019,
            'type' => 'movie',
            'genres' => array('Comedy', 'Drama', 'Thriller'),
            'rating' => 4.8,
            'duration' => '132 min',
            'language' => 'ko',
            'description' => 'Act like you own the place. A poor family, the Kims, con their way into becoming the servants of a rich family, the Parks. But their easy life gets complicated when their deception is threatened with exposure.',
            'director' => 'Bong Joon Ho',
            'cast' => array('Song Kang-ho', 'Lee Sun-kyun', 'Cho Yeo-jeong', 'Choi Woo-shik'),
            'poster_url' => 'https://via.placeholder.com/300x450/4B0082/FFFFFF?text=Parasite',
            'trailer_url' => 'https://www.youtube.com/embed/5xH0HfJHsaY'
        ),
        array(
            'title' => 'Inception',
            'year' => 2010,
            'type' => 'movie',
            'genres' => array('Action', 'Sci-Fi', 'Thriller'),
            'rating' => 4.8,
            'duration' => '148 min',
            'language' => 'en',
            'description' => 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.',
            'director' => 'Christopher Nolan',
            'cast' => array('Leonardo DiCaprio', 'Marion Cotillard', 'Tom Hardy', 'Ellen Page'),
            'poster_url' => 'https://via.placeholder.com/300x450/1E3A8A/FFFFFF?text=Inception',
            'trailer_url' => 'https://www.youtube.com/embed/YoHD9XEInc0'
        )
    );
}

/**
 * Sample TV show data
 */
function cinemabotpro_get_sample_tv_shows() {
    return array(
        array(
            'title' => 'Breaking Bad',
            'year' => 2008,
            'type' => 'tv_show',
            'genres' => array('Crime', 'Drama', 'Thriller'),
            'rating' => 4.9,
            'duration' => '5 seasons',
            'language' => 'en',
            'description' => 'A high school chemistry teacher diagnosed with inoperable lung cancer turns to manufacturing and selling methamphetamine in order to secure his family\'s future.',
            'director' => 'Vince Gilligan',
            'cast' => array('Bryan Cranston', 'Aaron Paul', 'Anna Gunn', 'RJ Mitte'),
            'poster_url' => 'https://via.placeholder.com/300x450/008000/FFFFFF?text=Breaking+Bad',
            'trailer_url' => 'https://www.youtube.com/embed/HhesaQXLuRY'
        ),
        array(
            'title' => 'Scam 1992',
            'year' => 2020,
            'type' => 'tv_show',
            'genres' => array('Biography', 'Crime', 'Drama'),
            'rating' => 4.8,
            'duration' => '1 season',
            'language' => 'hi',
            'description' => 'Set in 1980\'s & 90\'s Bombay, Scam 1992 follows the life of Harshad Mehta - a stockbroker who single-handedly took the stock market to dizzying heights & his catastrophic downfall.',
            'director' => 'Hansal Mehta',
            'cast' => array('Pratik Gandhi', 'Shreya Dhanwanthary', 'Hemant Kher', 'Anjali Barot'),
            'poster_url' => 'https://via.placeholder.com/300x450/B22222/FFFFFF?text=Scam+1992',
            'trailer_url' => 'https://www.youtube.com/embed/54BnjUzvsWg'
        ),
        array(
            'title' => 'Feluda',
            'year' => 2017,
            'type' => 'tv_show',
            'genres' => array('Mystery', 'Crime', 'Adventure'),
            'rating' => 4.5,
            'duration' => '2 seasons',
            'language' => 'bn',
            'description' => 'The adventures of Feluda, a detective created by Satyajit Ray. Each episode features a different mystery that Feluda solves with his assistant Topshe.',
            'director' => 'Srijit Mukherji',
            'cast' => array('Tota Roy Chowdhury', 'Kharaj Mukherjee', 'Anirban Chakrabarti'),
            'poster_url' => 'https://via.placeholder.com/300x450/8B4513/FFFFFF?text=Feluda',
            'trailer_url' => null
        ),
        array(
            'title' => 'Squid Game',
            'year' => 2021,
            'type' => 'tv_show',
            'genres' => array('Action', 'Drama', 'Mystery'),
            'rating' => 4.7,
            'duration' => '1 season',
            'language' => 'ko',
            'description' => 'Hundreds of cash-strapped players accept a strange invitation to compete in children\'s games. Inside, a tempting prize awaits with deadly high stakes.',
            'director' => 'Hwang Dong-hyuk',
            'cast' => array('Lee Jung-jae', 'Park Hae-soo', 'Wi Ha-joon', 'Jung Ho-yeon'),
            'poster_url' => 'https://via.placeholder.com/300x450/FF1493/FFFFFF?text=Squid+Game',
            'trailer_url' => 'https://www.youtube.com/embed/oqxAJKy0ii4'
        ),
        array(
            'title' => 'Stranger Things',
            'year' => 2016,
            'type' => 'tv_show',
            'genres' => array('Drama', 'Fantasy', 'Horror'),
            'rating' => 4.6,
            'duration' => '4 seasons',
            'language' => 'en',
            'description' => 'When a young boy disappears, his mother, a police chief and his friends must confront terrifying supernatural forces in order to get him back.',
            'director' => 'The Duffer Brothers',
            'cast' => array('Millie Bobby Brown', 'Finn Wolfhard', 'Winona Ryder', 'David Harbour'),
            'poster_url' => 'https://via.placeholder.com/300x450/800080/FFFFFF?text=Stranger+Things',
            'trailer_url' => 'https://www.youtube.com/embed/b9EkMc79ZSU'
        )
    );
}

/**
 * Sample chat responses for different languages
 */
function cinemabotpro_get_sample_responses() {
    return array(
        'en' => array(
            'welcome' => 'Hi! I\'m CinemaBot Pro. Ask me about movies, TV shows, or get personalized recommendations!',
            'popular_movies' => 'Here are some popular movies: The Dark Knight, Inception, Parasite, and Avengers: Endgame. Would you like to know more about any of these?',
            'recommendations' => 'Based on your preferences, I recommend checking out some critically acclaimed films like The Godfather, Pulp Fiction, or Schindler\'s List. What genre interests you most?',
            'new_releases' => 'Recent releases include Dune: Part Two, Oppenheimer, and Barbie. These have been getting great reviews! Want details about any of them?',
            'error' => 'Sorry, I encountered an error. Please try again or rephrase your question.'
        ),
        'bn' => array(
            'welcome' => 'হাই! আমি সিনেমাবট প্রো। আমাকে সিনেমা, টিভি শো সম্পর্কে জিজ্ঞাসা করুন বা ব্যক্তিগত সুপারিশ পান!',
            'popular_movies' => 'এখানে কিছু জনপ্রিয় সিনেমা: দ্য ডার্ক নাইট, ইনসেপশন, প্যারাসাইট, এবং অ্যাভেঞ্জার্স: এন্ডগেম। এদের মধ্যে কোনটি সম্পর্কে আরো জানতে চান?',
            'recommendations' => 'আপনার পছন্দের ভিত্তিতে, আমি সুপারিশ করি দ্য গডফাদার, পালপ ফিকশন, বা শিন্ডলার্স লিস্টের মতো প্রশংসিত ছবি দেখতে। কোন ধরনের সিনেমা আপনার বেশি পছন্দ?',
            'new_releases' => 'সাম্প্রতিক রিলিজের মধ্যে রয়েছে ডিউন: পার্ট টু, ওপেনহাইমার, এবং বার্বি। এগুলো দুর্দান্ত রিভিউ পাচ্ছে! কোনটি সম্পর্কে বিস্তারিত জানতে চান?',
            'error' => 'দুঃখিত, আমি একটি ত্রুটির সম্মুখীন হয়েছি। আবার চেষ্টা করুন বা আপনার প্রশ্নটি ভিন্নভাবে বলুন।'
        ),
        'hi' => array(
            'welcome' => 'हाय! मैं सिनेमाबॉट प्रो हूँ। मुझसे फिल्मों, टीवी शो के बारे में पूछें या व्यक्तिगत सिफारिशें पाएं!',
            'popular_movies' => 'यहाँ कुछ लोकप्रिय फिल्में हैं: द डार्क नाइट, इंसेप्शन, पैरासाइट, और एवेंजर्स: एंडगेम। क्या आप इनमें से किसी के बारे में और जानना चाहते हैं?',
            'recommendations' => 'आपकी पसंद के आधार पर, मैं द गॉडफादर, पल्प फिक्शन, या शिंडलर्स लिस्ट जैसी प्रशंसित फिल्में देखने की सिफारिश करता हूँ। आपको कौन सी शैली सबसे दिलचस्प लगती है?',
            'new_releases' => 'हाल की रिलीज़ में ड्यून: पार्ट टू, ओपेनहाइमर, और बार्बी शामिल हैं। इन्हें बेहतरीन समीक्षा मिल रही है! क्या आप इनमें से किसी के बारे में विवरण चाहते हैं?',
            'error' => 'क्षमा करें, मुझे एक त्रुटि का सामना करना पड़ा। कृपया पुनः प्रयास करें या अपना प्रश्न दूसरे तरीके से पूछें।'
        ),
        'banglish' => array(
            'welcome' => 'Hi! Ami CinemaBot Pro. Amake cinema, TV show niye jigges koro ba personal recommendation nao!',
            'popular_movies' => 'Ekhane kichhu popular cinema: The Dark Knight, Inception, Parasite, ar Avengers: Endgame. Egulor moddhe konta niye aro jante chao?',
            'recommendations' => 'Tomar preference onujayi, ami suggest korchi The Godfather, Pulp Fiction, ba Schindler\'s List er moto acclaimed cinema dekhte. Kon genre beshi interesting lage?',
            'new_releases' => 'Recent release gulo hoilo Dune: Part Two, Oppenheimer, ar Barbie. Egulo khub bhalo review pacche! Konta niye details chao?',
            'error' => 'Sorry, ami ekta error face korlam. Please abar try koro ba question ta different way te koro.'
        )
    );
}

/**
 * Sample genre data
 */
function cinemabotpro_get_sample_genres() {
    return array(
        'action' => 'Action',
        'adventure' => 'Adventure',
        'animation' => 'Animation',
        'biography' => 'Biography',
        'comedy' => 'Comedy',
        'crime' => 'Crime',
        'documentary' => 'Documentary',
        'drama' => 'Drama',
        'family' => 'Family',
        'fantasy' => 'Fantasy',
        'history' => 'History',
        'horror' => 'Horror',
        'music' => 'Music',
        'mystery' => 'Mystery',
        'romance' => 'Romance',
        'sci-fi' => 'Science Fiction',
        'sport' => 'Sport',
        'thriller' => 'Thriller',
        'war' => 'War',
        'western' => 'Western'
    );
}

/**
 * Create sample avatar file (placeholder creation)
 */
function cinemabotpro_create_sample_avatars() {
    $avatars_dir = CINEMABOTPRO_PLUGIN_DIR . 'assets/images/avatars/';
    
    // Create 50 placeholder avatar files
    for ($i = 1; $i <= 50; $i++) {
        $avatar_file = $avatars_dir . "avatar-{$i}.png";
        if (!file_exists($avatar_file)) {
            // Create a simple colored placeholder image
            $colors = array(
                '#FF6B35', '#F7931E', '#FFD23F', '#06FFA5', '#118AB2',
                '#073B4C', '#EF476F', '#F78C6B', '#06D6A0', '#4ECDC4',
                '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8'
            );
            $color = $colors[$i % count($colors)];
            
            // For now, just create an empty file as placeholder
            // In production, you would generate actual avatar images
            touch($avatar_file);
        }
    }
}

/**
 * Install sample data
 */
function cinemabotpro_install_sample_data() {
    // Create sample movie posts
    $movies = cinemabotpro_get_sample_movies();
    foreach ($movies as $movie) {
        $post_id = wp_insert_post(array(
            'post_title' => $movie['title'],
            'post_content' => $movie['description'],
            'post_status' => 'publish',
            'post_type' => 'movie',
            'meta_input' => array(
                'year' => $movie['year'],
                'rating' => $movie['rating'],
                'duration' => $movie['duration'],
                'language' => $movie['language'],
                'director' => $movie['director'],
                'cast' => implode(', ', $movie['cast']),
                'poster_url' => $movie['poster_url'],
                'trailer_url' => $movie['trailer_url']
            )
        ));
        
        if ($post_id && !is_wp_error($post_id)) {
            // Set genres
            wp_set_object_terms($post_id, $movie['genres'], 'movie_genre');
        }
    }
    
    // Create sample TV show posts
    $tv_shows = cinemabotpro_get_sample_tv_shows();
    foreach ($tv_shows as $show) {
        $post_id = wp_insert_post(array(
            'post_title' => $show['title'],
            'post_content' => $show['description'],
            'post_status' => 'publish',
            'post_type' => 'tv_show',
            'meta_input' => array(
                'year' => $show['year'],
                'rating' => $show['rating'],
                'duration' => $show['duration'],
                'language' => $show['language'],
                'director' => $show['director'],
                'cast' => implode(', ', $show['cast']),
                'poster_url' => $show['poster_url'],
                'trailer_url' => $show['trailer_url']
            )
        ));
        
        if ($post_id && !is_wp_error($post_id)) {
            // Set genres
            wp_set_object_terms($post_id, $show['genres'], 'tv_genre');
        }
    }
    
    // Create sample avatars
    cinemabotpro_create_sample_avatars();
    
    // Set sample data flag
    update_option('cinemabotpro_sample_data_installed', 1);
}