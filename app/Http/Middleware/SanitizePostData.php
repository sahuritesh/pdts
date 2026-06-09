<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizePostData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('post')) {
            $this->sanitize($request);
        }
        return $next($request);
    }

    /**
     * Sanitize the request data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function sanitize(Request $request)
    {
        $data = $request->all();

        // Fields that should preserve HTML content (TinyMCE editor fields, etc.)
        // This list includes all fields that use TinyMCE editor across the application
        $htmlFields = [
            // General content fields
            'description',
            'template_body',
            'body',
            'bio',
            'content',
            'notes',
            'message',
            'email_body',
            
            // CMS Section fields
            'content_en',              // CMS section content (English)
            'content_hi',              // CMS section content (Hindi)
            'excerpt_en',              // CMS section excerpt (English)
            'excerpt_hi',              // CMS section excerpt (Hindi)
            
            // Cards section fields
            'cards_additional_content_en',
            'cards_additional_content_hi',
            
            // Highlights section fields
            'highlights_additional',
            'highlights_sticky_description',
            'highlights_sticky_box_text',
            'highlights_sticky_note_text_en',
            'highlights_sticky_note_text_hi',
            
            // FAQ fields
            'answer',                  // Session question answers (TinyMCE content)
            'answer_en',               // FAQ answer (English) - TinyMCE content
            'answer_hi',               // FAQ answer (Hindi) - TinyMCE content
            'question_en',             // FAQ question (English) - may contain HTML
            'question_hi',             // FAQ question (Hindi) - may contain HTML
            
            // Faculty fields
            'faculty_bio_en',
            'faculty_bio_hi',
            
            // Tab fields
            'tab_content_en',
            'tab_content_hi',
            
            // Slide fields
            'slide_content_en',
            'slide_content_hi'
        ];

        // Fields that contain JSON data and should be processed specially
        $jsonFields = ['data'];

        // Loop through the POST data and sanitize it
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Handle JSON fields - decode, sanitize nested fields, re-encode
                if (in_array($key, $jsonFields)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        // Recursively sanitize nested data
                        $decoded = $this->sanitizeArray($decoded, $htmlFields);
                        $data[$key] = json_encode($decoded);
                    } else {
                        // If not valid JSON, treat as regular string
                        if (in_array($key, $htmlFields)) {
                            $data[$key] = trim($value);
                        } else {
                            $data[$key] = trim(strip_tags($value));
                        }
                    }
                } elseif ($this->isHtmlField($key, $htmlFields)) {
                    // Skip HTML fields - preserve HTML content
                    // Only trim whitespace, don't strip HTML tags
                    $data[$key] = trim($value);
                } else {
                    // Strip HTML tags and trim whitespace for other fields
                    $data[$key] = trim(strip_tags($value));
                }
            }
        }

        // Normalize DataTable/grid filters payload across pages.
        // Some requests may send "filters" as JSON string or query-string style text.
        if (isset($data['filters']) && is_string($data['filters'])) {
            $filtersString = trim($data['filters']);
            if ($filtersString !== '') {
                $decodedFilters = null;

                // JSON object/array format
                if (strpos($filtersString, '{') === 0 || strpos($filtersString, '[') === 0) {
                    $decoded = json_decode($filtersString, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $decodedFilters = $decoded;
                    }
                }

                // Query-string format: key1=val1&key2=val2
                if ($decodedFilters === null && strpos($filtersString, '=') !== false) {
                    $parsedFilters = [];
                    parse_str($filtersString, $parsedFilters);
                    if (is_array($parsedFilters)) {
                        $decodedFilters = $parsedFilters;
                    }
                }

                if (is_array($decodedFilters)) {
                    $data['filters'] = $decodedFilters;
                }
            }
        }
        // Replace the request data with the sanitized data
        $request->merge($data);
    }

    /**
     * Recursively sanitize array data, preserving HTML fields
     *
     * @param array $data
     * @param array $htmlFields
     * @return array
     */
    protected function sanitizeArray($data, $htmlFields)
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Preserve HTML content for HTML fields
                if ($this->isHtmlField($key, $htmlFields)) {
                    // Only trim whitespace, don't strip HTML tags
                    $data[$key] = trim($value);
                } else {
                    // Strip HTML tags and trim whitespace for other fields
                    $data[$key] = trim(strip_tags($value));
                }
            } elseif (is_array($value)) {
                // Recursively sanitize nested arrays
                $data[$key] = $this->sanitizeArray($value, $htmlFields);
            }
        }
        return $data;
    }
    
    /**
     * Check if a field key should preserve HTML content (TinyMCE fields)
     *
     * @param string $key
     * @param array $htmlFields
     * @return bool
     */
    protected function isHtmlField($key, $htmlFields)
    {
        // Direct match in htmlFields array
        if (in_array($key, $htmlFields)) {
            return true;
        }
        
        // Pattern matching for common TinyMCE field patterns:
        // 1. Standard bilingual fields: {field}_en, {field}_hi
        //    Examples: content_en, content_hi, excerpt_en, excerpt_hi, answer_en, answer_hi, question_en, question_hi
        //    Also: faculty_bio_en, faculty_bio_hi, tab_content_en, tab_content_hi, slide_content_en, slide_content_hi
        if (preg_match('/^(content|excerpt|answer|question|bio|description|definition|text|note|additional|sticky|box)_(en|hi)$/', $key)) {
            return true;
        }
        
        // 2. Array-based fields (for dynamic items):
        //    - cards[{index}][content_en], cards[{index}][content_hi]
        //    - glossary_items[{index}][definition_en], glossary_items[{index}][definition_hi]
        //    - highlights_items[{index}][description_en], highlights_items[{index}][description_hi]
        //    - highlights_sticky_fee_rows[{index}][note_en], highlights_sticky_fee_rows[{index}][note_hi]
        //    - highlights_sticky_box_list_items[{index}][text_en], highlights_sticky_box_list_items[{index}][text_hi]
        if (preg_match('/\[(content|excerpt|answer|question|bio|description|definition|text|note|additional|sticky|box)_(en|hi)\]$/', $key)) {
            return true;
        }
        
        // 3. Fields containing "content" with language suffix
        //    Examples: cards_additional_content_en, cards_additional_content_hi
        if (preg_match('/content.*_(en|hi)$/', $key)) {
            return true;
        }
        
        // 4. Fields that contain common HTML field names
        //    Examples: highlights_additional, highlights_sticky_description, highlights_sticky_box_text
        if (preg_match('/(content|excerpt|answer|question|bio|description|definition|text|note|additional|sticky|box|body|template_body)$/', $key)) {
            // But exclude fields that are clearly not HTML (like IDs, prices, etc.)
            if (!preg_match('/(_id|_price|_amount|_count|_number|_date|_time|_url|_link|_icon|_image|_color|_bg_color|_style|_type|_enabled|_active)$/', $key)) {
                return true;
            }
        }
        
        return false;
    }
}
