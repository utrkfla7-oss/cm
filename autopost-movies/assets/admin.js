jQuery(document).ready(function($) {
    
    // Manual sync button
    $('#apm-manual-sync').on('click', function() {
        var $button = $(this);
        var $status = $('#apm-sync-status');
        
        $button.prop('disabled', true).text('Running...');
        $status.show().html('<div class="notice notice-info"><p>Starting manual sync...</p></div>');
        
        $.ajax({
            url: apm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'apm_manual_sync',
                nonce: apm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    if (response.data.errors && response.data.errors.length > 0) {
                        $status.append('<div class="notice notice-warning"><p>Errors: ' + response.data.errors.join(', ') + '</p></div>');
                    }
                } else {
                    $status.html('<div class="notice notice-error"><p>Sync failed: ' + response.data + '</p></div>');
                }
            },
            error: function() {
                $status.html('<div class="notice notice-error"><p>AJAX request failed</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Run Manual Sync');
            }
        });
    });
    
    // View logs button
    $('#apm-view-logs').on('click', function() {
        var $container = $('#apm-logs-container');
        var $content = $('#apm-logs-content');
        
        if ($container.is(':visible')) {
            $container.hide();
            return;
        }
        
        $content.html('<p>Loading logs...</p>');
        $container.show();
        
        $.ajax({
            url: apm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'apm_get_logs',
                nonce: apm_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var html = '<div class="apm-logs-table">';
                    html += '<table class="wp-list-table widefat">';
                    html += '<thead><tr><th>Time</th><th>Level</th><th>Message</th></tr></thead>';
                    html += '<tbody>';
                    
                    $.each(response.data, function(i, log) {
                        var levelClass = 'apm-log-' + log.level;
                        html += '<tr class="' + levelClass + '">';
                        html += '<td>' + log.timestamp + '</td>';
                        html += '<td>' + log.level.toUpperCase() + '</td>';
                        html += '<td>' + log.message + '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div>';
                    $content.html(html);
                } else {
                    $content.html('<p>No logs found.</p>');
                }
            },
            error: function() {
                $content.html('<p>Failed to load logs.</p>');
            }
        });
    });
    
    // Clear logs button
    $('#apm-clear-logs').on('click', function() {
        if (!confirm('Are you sure you want to clear all logs?')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true);
        
        $.ajax({
            url: apm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'apm_clear_logs',
                nonce: apm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#apm-logs-content').html('<p>Logs cleared successfully.</p>');
                } else {
                    alert('Failed to clear logs.');
                }
            },
            error: function() {
                alert('AJAX request failed.');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
    
    // Test API connections (if button exists)
    $('#apm-test-apis').on('click', function() {
        var $button = $(this);
        var $results = $('#apm-api-test-results');
        
        $button.prop('disabled', true).text('Testing...');
        $results.html('<p>Testing API connections...</p>').show();
        
        $.ajax({
            url: apm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'apm_test_apis',
                nonce: apm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var html = '<h4>API Test Results:</h4><ul>';
                    $.each(response.data, function(api, status) {
                        var statusText = status ? 'Connected' : 'Failed';
                        var statusClass = status ? 'success' : 'error';
                        html += '<li class="apm-api-status-' + statusClass + '">' + 
                               api.toUpperCase() + ': ' + statusText + '</li>';
                    });
                    html += '</ul>';
                    $results.html(html);
                } else {
                    $results.html('<p class="error">Test failed.</p>');
                }
            },
            error: function() {
                $results.html('<p class="error">AJAX request failed.</p>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Test API Connections');
            }
        });
    });
    
    // Auto-hide notices after 5 seconds
    setTimeout(function() {
        $('.notice').fadeOut();
    }, 5000);
});