<?php
if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Google_Reviews {
    
    // Transient keys
    const TRANSIENT_RATING_DATA = 'wiwa_google_rating_data';
    const CACHE_TIME = DAY_IN_SECONDS; // 24 hours

    /**
     * Get the dynamic rating data from Google Places.
     * Caches the result using transients.
     * 
     * @return array|false Returns ['rating' => 4.7, 'count' => 375] or false on failure.
     */
    public static function get_rating_data() {
        // Try getting from cache first
        $cached_data = get_transient(self::TRANSIENT_RATING_DATA);
        
        if ($cached_data !== false) {
            return $cached_data;
        }

        // Fetch fresh data if needed
        $api_key = get_option('wiwa_google_places_api_key');
        $place_id = get_option('wiwa_google_place_id');

        if (empty($api_key) || empty($place_id)) {
            return false;
        }

        $url = add_query_arg([
            'place_id' => $place_id,
            'fields'   => 'rating,user_ratings_total',
            'key'      => $api_key
        ], 'https://maps.googleapis.com/maps/api/place/details/json');

        $response = wp_remote_get($url, [
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            // Log error or handle fallback silently
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['status']) && $data['status'] === 'OK' && isset($data['result'])) {
            $rating_info = [
                'rating' => isset($data['result']['rating']) ? floatval($data['result']['rating']) : 0,
                'count'  => isset($data['result']['user_ratings_total']) ? intval($data['result']['user_ratings_total']) : 0,
            ];

            // Cache it for 24 hours
            set_transient(self::TRANSIENT_RATING_DATA, $rating_info, self::CACHE_TIME);
            return $rating_info;
        }

        return false;
    }

    /**
     * Clear the cache. Useful for settings saves.
     */
    public static function clear_cache() {
        delete_transient(self::TRANSIENT_RATING_DATA);
    }
}
