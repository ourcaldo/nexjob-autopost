<?php
/**
 * Autopost configurations management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexjob_Configs {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'nexjob_autopost_configs';
    }
    
    /**
     * Get all autopost configurations
     */
    public function get_configs($status = null) {
        global $wpdb;
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(' WHERE status = %s', $status);
        }
        
        return $wpdb->get_results("SELECT * FROM {$this->table_name}{$where} ORDER BY created_at ASC");
    }
    
    /**
     * Get single configuration
     */
    public function get_config($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id));
    }
    
    /**
     * Create new configuration
     */
    public function create_config($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'name' => sanitize_text_field($data['name']),
                'post_types' => json_encode($data['post_types']),
                'integration_id' => sanitize_text_field($data['integration_id']),
                'content_template' => wp_kses_post($data['content_template']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update configuration
     */
    public function update_config($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            array(
                'name' => sanitize_text_field($data['name']),
                'post_types' => json_encode($data['post_types']),
                'integration_id' => sanitize_text_field($data['integration_id']),
                'content_template' => wp_kses_post($data['content_template']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('id' => intval($id)),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Delete configuration
     */
    public function delete_config($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => intval($id)),
            array('%d')
        );
    }
    
    /**
     * Get configurations for specific post type
     */
    public function get_configs_for_post_type($post_type) {
        $configs = $this->get_configs('active');
        $matching_configs = array();
        
        foreach ($configs as $config) {
            $post_types = json_decode($config->post_types, true);
            if (in_array($post_type, $post_types)) {
                $matching_configs[] = $config;
            }
        }
        
        return $matching_configs;
    }
    
    /**
     * Get all available post types
     */
    public function get_available_post_types() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $custom_post_types = get_post_types(array('public' => false, '_builtin' => false), 'objects');
        
        $all_types = array_merge($post_types, $custom_post_types);
        $formatted_types = array();
        
        foreach ($all_types as $post_type) {
            $formatted_types[$post_type->name] = $post_type->label;
        }
        
        return $formatted_types;
    }
    
    /**
     * Get all custom fields for a post type
     */
    public function get_custom_fields($post_type = null) {
        global $wpdb;
        
        $query = "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE meta_key NOT LIKE '\_%'";
        
        if ($post_type) {
            $query .= $wpdb->prepare(" AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)", $post_type);
        }
        
        $query .= " ORDER BY meta_key ASC LIMIT 100";
        
        $results = $wpdb->get_col($query);
        
        return $results ? $results : array();
    }
    
    /**
     * Get all taxonomies for a post type
     */
    public function get_taxonomies($post_type = null) {
        if ($post_type) {
            $taxonomies = get_object_taxonomies($post_type, 'objects');
        } else {
            $taxonomies = get_taxonomies(array('public' => true), 'objects');
        }
        
        $formatted_taxonomies = array();
        
        foreach ($taxonomies as $taxonomy) {
            $formatted_taxonomies[$taxonomy->name] = $taxonomy->label;
        }
        
        return $formatted_taxonomies;
    }
    
    /**
     * Parse content template with placeholders
     */
    public function parse_content_template($template, $post) {
        $content = $template;
        
        // Replace basic post placeholders
        $content = str_replace('{{post_title}}', $post->post_title, $content);
        $content = str_replace('{{post_url}}', get_permalink($post->ID), $content);
        $content = str_replace('{{post_content}}', strip_tags($post->post_content), $content);
        $content = str_replace('{{post_excerpt}}', $post->post_excerpt, $content);
        $content = str_replace('{{post_date}}', $post->post_date, $content);
        
        // Replace post URL with nexjob.tech domain
        $content = str_replace('cms.nexjob.tech', 'nexjob.tech', $content);
        
        // Replace custom field placeholders
        if (preg_match_all('/\{\{([^:}]+)\}\}/', $content, $matches)) {
            foreach ($matches[1] as $field_name) {
                $field_value = get_post_meta($post->ID, $field_name, true);
                $content = str_replace('{{' . $field_name . '}}', $field_value, $content);
            }
        }
        
        // Replace taxonomy hashtags
        if (preg_match_all('/\{\{hashtags:([^}]+)\}\}/', $content, $matches)) {
            foreach ($matches[1] as $taxonomy) {
                $terms = wp_get_post_terms($post->ID, $taxonomy);
                $hashtags = array();
                
                if (!is_wp_error($terms) && !empty($terms)) {
                    foreach ($terms as $term) {
                        // Split term names on "/" and "-" characters
                        $term_parts = preg_split('/[\/\-]/', $term->name);
                        foreach ($term_parts as $part) {
                            $part = trim($part);
                            if (!empty($part)) {
                                // Remove spaces and add hashtag
                                $hashtags[] = '#' . str_replace(' ', '', $part);
                            }
                        }
                    }
                }
                
                $hashtag_string = implode(' ', $hashtags);
                $content = str_replace('{{hashtags:' . $taxonomy . '}}', $hashtag_string, $content);
            }
        }
        
        // Replace taxonomy terms (non-hashtag)
        if (preg_match_all('/\{\{terms:([^}]+)\}\}/', $content, $matches)) {
            foreach ($matches[1] as $taxonomy) {
                $terms = wp_get_post_terms($post->ID, $taxonomy);
                $term_names = array();
                
                if (!is_wp_error($terms) && !empty($terms)) {
                    foreach ($terms as $term) {
                        $term_names[] = $term->name;
                    }
                }
                
                $terms_string = implode(', ', $term_names);
                $content = str_replace('{{terms:' . $taxonomy . '}}', $terms_string, $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Get placeholder variables for content editor
     */
    public function get_placeholder_variables($post_type = null) {
        $variables = array(
            'Basic Post Fields' => array(
                '{{post_title}}' => 'Post title',
                '{{post_url}}' => 'Post URL/permalink',
                '{{post_content}}' => 'Post content (stripped of HTML)',
                '{{post_excerpt}}' => 'Post excerpt',
                '{{post_date}}' => 'Post publication date'
            )
        );
        
        // Add custom fields
        $custom_fields = $this->get_custom_fields($post_type);
        if (!empty($custom_fields)) {
            $variables['Custom Fields'] = array();
            foreach ($custom_fields as $field) {
                $variables['Custom Fields']['{{' . $field . '}}'] = 'Custom field: ' . $field;
            }
        }
        
        // Add taxonomies
        $taxonomies = $this->get_taxonomies($post_type);
        if (!empty($taxonomies)) {
            $variables['Taxonomies (Hashtags)'] = array();
            $variables['Taxonomies (Terms)'] = array();
            foreach ($taxonomies as $tax_name => $tax_label) {
                $variables['Taxonomies (Hashtags)']['{{hashtags:' . $tax_name . '}}'] = 'Hashtags from ' . $tax_label;
                $variables['Taxonomies (Terms)']['{{terms:' . $tax_name . '}}'] = 'Terms from ' . $tax_label;
            }
        }
        
        return $variables;
    }
}
?>