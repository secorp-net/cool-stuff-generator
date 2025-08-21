<?php
// Configuration and functions
// Debug function to output to page when needed
function debug_to_page($message) {
    // Uncomment the line below to enable direct debug output to the page
    // echo "<!-- DEBUG: " . htmlspecialchars($message) . " -->\n";
    error_log($message);
}

// Function to check environment variables from multiple sources
function check_env_var($name) {
    $result = [
        'getenv' => getenv($name),
        'getenv_local_only' => @getenv($name, true),
        '_SERVER' => isset($_SERVER[$name]) ? $_SERVER[$name] : null,
        '_ENV' => isset($_ENV[$name]) ? $_ENV[$name] : null,
        'apache_getenv' => function_exists('apache_getenv') ? @apache_getenv($name) : 'function_not_available',
        'putenv_test' => false
    ];
    
    // Try setting and getting a test variable
    $test_var = 'TEST_VAR_' . rand(1000, 9999);
    @putenv($test_var . '=test_value');
    $result['putenv_test'] = getenv($test_var) === 'test_value' ? 'YES' : 'NO';
    
    return $result;
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to file - using system temp directory for permissions
ini_set('log_errors', 1);
ini_set('error_log', sys_get_temp_dir() . '/cool-stuff-error.log');

// Create a debug file and log the environment
$env_debug = [
    'script_file' => __FILE__,
    'script_dir' => __DIR__,
    'server_script' => $_SERVER['SCRIPT_FILENAME'],
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'temp_dir' => sys_get_temp_dir(),
    'uid' => getmyuid(),
    'gid' => getmygid(),
    'permissions' => @fileperms(__FILE__) ? decoct(fileperms(__FILE__)) : 'unknown',
    'openai_api_key_exists' => getenv('OPENAI_API_KEY') ? 'YES (length: ' . strlen(getenv('OPENAI_API_KEY')) . ')' : 'NO',
    'apache_env_vars' => function_exists('apache_getenv') ? 'Available' : 'Not available',
    'openai_api_key_check' => check_env_var('OPENAI_API_KEY'),
    'php_version' => phpversion(),
];

// Try setting environment variable directly to test permissions
@putenv('TEST_OPENAI_API_KEY=sk-test-key');
$env_debug['putenv_test_result'] = getenv('TEST_OPENAI_API_KEY');
@file_put_contents(sys_get_temp_dir() . '/cool-stuff-environment.txt', 
    print_r($env_debug, true), FILE_APPEND);
$api_key = ''; // This will be filled in by the user through the form

// Categories and items
$categories = [
    'work' => [
        "Remote coding session",
        "Co-working space",
        "Professional workshop",
        "Networking event",
        "Creative brainstorming",
        "Tech meetup"
    ],
    'sports' => [
        "Rock climbing",
        "Martial arts class",
        "Hiking",
        "Paddleboarding",
        "Cycling",
        "Yoga"
    ],
    'social' => [
        "Board game night",
        "Live music show",
        "Art gallery opening",
        "Outdoor movie screening",
        "Local meetup group",
        "Meeting new people"
    ],
    'food' => [
        "Sushi restaurant",
        "Mexican taqueria",
        "Gourmet pizza",
        "Farm-to-table dining",
        "Food truck festival",
        "Dumpling time",
        "BBQ"
    ]
];

// Get OpenAI API response
function getOpenAIRecommendations($api_key, $location, $selected_items, $action = 'generate') {
    // Create prompt for OpenAI
    // Check if this is a single-item request
    $is_single_item = ($action === 'generate_single_item');
    
    if ($is_single_item) {
        // For single-item requests, create a more focused prompt
        $single_category = '';
        $single_activity = '';
        
        // Find the single item that was requested
        foreach ($selected_items as $cat => $items) {
            if (count($items) == 1) {
                $single_category = $cat;
                $single_activity = $items[0];
                break;
            }
        }
        
        $prompt = "I'm in {$location} and I'm specifically interested in {$single_activity}. Please provide EXACTLY ONE real recommendation with full details for this activity.\n\n";
        $prompt .= "I want a detailed recommendation for {$single_activity} that includes all of the following: name, description, website, exact address, and why it's a good option for this activity.\n\n";
    } else {
        // Regular prompt for multiple items
        $prompt = "I'm in {$location} and looking for specific recommendations with real locations, websites, and addresses for the following activities:\n\n";
    }
    
    // Debug: Log the location
    error_log("DEBUG - Location: " . $location);
    
    foreach ($selected_items as $category => $items) {
        if (!empty($items)) {
            $prompt .= strtoupper($category) . ": " . implode(', ', $items) . "\n";
        }
    }
    
    if ($is_single_item) {
        $prompt .= "\nPlease provide EXACTLY ONE real recommendation for this specific activity. Format your response EXACTLY like this:
        
BUSINESS NAME: [Name]
DESCRIPTION: [Brief description]
WEBSITE: [Website]
ADDRESS: [Address]
NOTE: [Why it's good for this activity]";
    } else {
        $prompt .= "\nPlease suggest EXACTLY 2 ACTUAL places near my location for each activity. For each category, format your response EXACTLY like this:

WORK:

1. BUSINESS NAME: [Name]
   DESCRIPTION: [Brief description]
   WEBSITE: [Website]
   ADDRESS: [Address]
   NOTE: [Why it's good]

2. BUSINESS NAME: [Name]
   DESCRIPTION: [Brief description]
   WEBSITE: [Website]
   ADDRESS: [Address]
   NOTE: [Why it's good]

SPORTS:

1. BUSINESS NAME: [Name]
   DESCRIPTION: [Brief description]
   WEBSITE: [Website]
   ADDRESS: [Address]
   NOTE: [Why it's good]

(And so on for SOCIAL and FOOD)

VERY IMPORTANT: 
1. Start each category with JUST the category name in all caps followed by a colon (e.g., 'WORK:') on its own line
2. Number the recommendations as 1. and 2.
3. Use the exact labels BUSINESS NAME, DESCRIPTION, WEBSITE, ADDRESS, and NOTE
4. Provide EXACTLY 2 recommendations per category
5. All recommendations MUST be real places that actually exist
6. Keep a blank line between each category and between recommendations";
    }
    
    // Prepare the API request
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => $is_single_item ? 
                    'You are a helpful assistant that provides specific, real location recommendations with accurate addresses. You always research actual places in the user\'s location, not generic or made-up places. The recommendation must be a real business or location with an accurate address.

You must provide EXACTLY 1 recommendation for the specific activity requested. Follow the exact formatting provided in the user\'s prompt. This strict format is critical for proper parsing by the application.' :
                    'You are a helpful assistant that provides specific, real location recommendations with accurate addresses. You always research actual places in the user\'s location, not generic or made-up places. All of your recommendations must be real businesses or locations with accurate addresses. 

For each category, you must provide EXACTLY 2 recommendations - no more, no less. Follow the exact formatting provided in the user\'s prompt. Make sure each category starts with its name on a dedicated line (e.g., "WORK:") with no other text on that line. This strict format is critical for proper parsing by the application.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7
    ];
    
    // Set up cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    // Debug: Log the raw API request
    error_log("DEBUG - API Request: " . json_encode($data));
    
    // Execute the request
    $response = curl_exec($ch);
    
    // Debug: Log the raw API response
    error_log("DEBUG - Raw API Response: " . substr($response, 0, 1000) . (strlen($response) > 1000 ? "..." : ""));
    
    if (curl_errno($ch)) {
        error_log("DEBUG - cURL Error: " . curl_error($ch));
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Process response
    $result = json_decode($response, true);
    
    if ($result === null) {
        error_log('JSON decode error: ' . json_last_error_msg() . ' - Raw response: ' . substr($response, 0, 1000));
        throw new Exception('Invalid response from OpenAI API: ' . json_last_error_msg());
    }
    
    if (isset($result['error'])) {
        error_log('OpenAI API error: ' . print_r($result['error'], true));
        throw new Exception(isset($result['error']['message']) ? $result['error']['message'] : 'Unknown API error');
    }
    
    if (!isset($result['choices']) || !isset($result['choices'][0]['message']['content'])) {
        error_log('Unexpected API response structure: ' . print_r($result, true));
        throw new Exception('Unexpected response format from OpenAI API');
    }
    
    return $result['choices'][0]['message']['content'];
}

// Process form submission
$results = [];
$error = '';
$selected_items = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's an AJAX request for recommendations
    if (isset($_POST['action'])) {
        try {
            // Different actions for different request types
            $action = $_POST['action'];
                        // Get API key from environment, secret, and location
             $api_key = getenv('OPENAI_API_KEY');
             
             // Log API key status (without revealing the full key)
             $debug_file = sys_get_temp_dir() . '/api_key_methods.txt';
             $debug_data = "Environment API Key: " . (!empty($api_key) ? "Found" : "Not found") . "\n";
             if (!empty($api_key)) {
                $debug_data .= "Key starts with: " . substr($api_key, 0, 5) . "...\n";
             }
             @file_put_contents($debug_file, $debug_data, FILE_APPEND);
             
             // Default cities array (same as in the HTML section)
             $default_cities = [
                "San Francisco, CA",
                "New York, NY",
                "Tokyo, Japan",
                "Paris, France",
                "London, UK",
                "Berlin, Germany",
                "Moscow, Russia",
                "Tel Aviv, Israel",
                "Dubai, UAE",
                "Kuala Lumpur, Malaysia",
                "Bangkok, Thailand",
                "Singapore",
                "Sydney, Australia",
                "Beijing, China",
                "Hong Kong",
                "Houghton, MI"
             ];
             
             // Get the super secret value
             $super_secret = isset($_POST['super_secret']) ? $_POST['super_secret'] : '';
             $super_secret_env_value = getenv('SUPER_SECRET');
             
             // Get the location or use a random default if not provided
             $location = isset($_POST['location']) && !empty($_POST['location']) 
                      ? $_POST['location'] 
                      : $default_cities[array_rand($default_cities)];
              
                         // Verify the API key exists in the environment
            if (empty($api_key) || substr($api_key, 0, 3) !== 'sk-') {
               throw new Exception('Server configuration issue: API key not found in environment');
            }
           
           // Check the super secret value
           if ($super_secret !== $super_secret_env_value) {
               debug_to_page("DEBUG - Invalid super secret: " . $super_secret);
               
               // Try to write to a debug file
               $debug_file = dirname($_SERVER['SCRIPT_FILENAME']) . '/debug_output.txt';
               @file_put_contents($debug_file, "Invalid secret attempt: " . $super_secret . "\n", FILE_APPEND);
               
               // Also write to system temp dir which should be writable
               @file_put_contents(sys_get_temp_dir() . '/cool-stuff-debug.txt', 
                   "Invalid secret attempt: " . $super_secret . "\n", FILE_APPEND);
               
               throw new Exception('Invalid secret code');
           }
           
           if (empty($location)) {
               throw new Exception('Please enter your location');
           }
           
           // Handle different action types
           if ($action === 'generate') {
               // Standard generation: Select exactly 2 random items from each category
               foreach ($categories as $category => $items) {
                   shuffle($items);
                   $selected_items[$category] = array_slice($items, 0, 2);
               }
           } 
           elseif ($action === 'generate_single_item') {
               // Single item generation: Generate recommendations for a specific item
               $category = isset($_POST['category']) ? $_POST['category'] : '';
               $activity = isset($_POST['activity']) ? $_POST['activity'] : '';
               
               // Write to debug file
               $debug_file = dirname($_SERVER['SCRIPT_FILENAME']) . '/debug_output.txt';
               @file_put_contents($debug_file, "Single item request received: category=$category, activity=$activity\n", FILE_APPEND);
               error_log("Single item request received: category=$category, activity=$activity");
               
               if (empty($category) || empty($activity)) {
                   $error_msg = 'Missing category or activity for recommendation';
                   error_log($error_msg);
                   @file_put_contents($debug_file, "Error: $error_msg\n", FILE_APPEND);
                   throw new Exception($error_msg);
               }
               
               if (!array_key_exists($category, $categories)) {
                   $error_msg = 'Invalid category: ' . $category;
                   error_log($error_msg);
                   @file_put_contents($debug_file, "Error: $error_msg\n", FILE_APPEND);
                   throw new Exception($error_msg);
               }
               
               // Verify the activity exists in this category
               $valid_activity = false;
               foreach ($categories[$category] as $item) {
                   if ($item === $activity) {
                       $valid_activity = true;
                       break;
                   }
               }
               
               // If not found, still proceed but log it
               if (!$valid_activity) {
                   error_log("WARNING: Activity '$activity' not found in category '$category' but proceeding anyway");
                   @file_put_contents($debug_file, "WARNING: Activity '$activity' not found in category '$category' but proceeding anyway\n", FILE_APPEND);
               }
               
               // Create a single item request
               foreach ($categories as $cat => $items) {
                   if ($cat === $category) {
                       // For the requested category, only include the specific activity
                       $selected_items[$cat] = [$activity];
                       @file_put_contents($debug_file, "Added activity '$activity' to category '$cat'\n", FILE_APPEND);
                   } else {
                       // For other categories, leave empty
                       $selected_items[$cat] = [];
                   }
               }
               
               // Log what we're doing
               error_log("Generating single recommendation for category: $category, activity: $activity");
               @file_put_contents($debug_file, "Generating single recommendation for category: $category, activity: $activity\n", FILE_APPEND);
           }
           else {
               throw new Exception('Invalid action type');
           }
            
            // Debug: Store the prompt for debugging
            $debug_prompt = "";
            foreach ($selected_items as $category => $items) {
                $debug_prompt .= strtoupper($category) . ": " . implode(', ', $items) . "\n";
            }
            
            // Get recommendations from OpenAI
            $response = getOpenAIRecommendations($api_key, $location, $selected_items, $action);
            
            // Debug: Add prompt and response to results for debugging
            $debug_info = [
                'debug_prompt' => $debug_prompt,
                'debug_raw_response' => $response
            ];
            
            // Process the response to split by category
            $sections = [];
            
            // Debug: Log the full response for inspection
            debug_to_page("DEBUG - Full Response to parse: " . $response);
            
            // Alternative debugging - write to a file in the current directory
            // Try to write to a file in the web directory if we have permissions
            $debug_file = dirname($_SERVER['SCRIPT_FILENAME']) . '/debug_output.txt';
            @file_put_contents($debug_file, "Full Response: " . $response . "\n\n", FILE_APPEND);
            
            // Different parsing logic for single items vs. multiple items
            if ($action === 'generate_single_item') {
                // For single items, just put the entire response in the specified category
                $target_category = '';
                // Find the category that has a single item
                foreach ($selected_items as $cat => $items) {
                    if (count($items) === 1) {
                        $target_category = $cat;
                        break;
                    }
                }
                
                @file_put_contents($debug_file, "Single item mode - assigning content to category: $target_category\n", FILE_APPEND);
                
                if (!empty($target_category)) {
                    // Just assign the entire response to this category
                    $sections[$target_category] = $response;
                    
                    // Log what we're doing
                    @file_put_contents($debug_file, "Assigned response to category $target_category\n", FILE_APPEND);
                }
            } else {
                // Standard multi-category parsing
                // Define pattern to look for category headers (must be on their own line)
                // Using \b word boundary and making the pattern more specific to match only standalone category headers
                $category_patterns = [
                    '/^\s*\bWORK\s*:?\s*$/i' => 'work',
                    '/^\s*\bSPORTS\s*:?\s*$/i' => 'sports',
                    '/^\s*\bSOCIAL\s*:?\s*$/i' => 'social',
                    '/^\s*\bFOOD\s*:?\s*$/i' => 'food'
                ];
                
                // First, split the text by clear category headers
                $content_by_category = [];
                $current_category = null;
                
                // Split the response by lines 
                $lines = explode("\n", $response);
                $all_content = [];
                
                // Enhanced parsing of category sections with better handling of numbered entries
                foreach ($lines as $line_idx => $line) {
                    $trimmed_line = trim($line);
                    $matched = false;
                    
                    // Debug the line being processed
                    @file_put_contents($debug_file, "Processing line: " . $trimmed_line . "\n", FILE_APPEND);
                    
                    // Skip empty lines but track them to preserve formatting in output
                    if (empty($trimmed_line)) {
                        // If we're already in a category, preserve empty lines
                        if ($current_category) {
                            $content_by_category[$current_category][] = ''; // Keep empty line
                        }
                        continue;
                    }
                    
                    // Check if this line is a clear category header (exact match for category names)
                    foreach ($category_patterns as $pattern => $category_key) {
                        if (preg_match($pattern, $trimmed_line)) {
                            // We found a new category
                            @file_put_contents($debug_file, "Found category header: " . $category_key . "\n", FILE_APPEND);
                            $current_category = $category_key;
                            $content_by_category[$current_category] = [];
                            $matched = true;
                            break;
                        }
                    }
                    
                    // If not a category header and we have a current category, add to it
                    if (!$matched && $current_category) {
                        $content_by_category[$current_category][] = $trimmed_line;
                    }
                }
                
                // Now convert each category's content to string and ensure the entire content is preserved
                foreach ($content_by_category as $category => $lines) {
                    $sections[$category] = implode("\n", $lines);
                    
                    // Check if this section contains business name entries
                    $hasBusinessEntries = false;
                    foreach ($lines as $line) {
                        if (strpos($line, 'BUSINESS NAME:') !== false) {
                            $hasBusinessEntries = true;
                            break;
                        }
                    }
                    
                    // Log information about this category's content
                    @file_put_contents($debug_file, "Category {$category} contains " . count($lines) . " lines. " . 
                                                 "Has business entries: " . ($hasBusinessEntries ? "YES" : "NO") . "\n", FILE_APPEND);
                }
            }
            
            // Debug log the parsed sections
            debug_to_page("DEBUG - Parsed sections: " . print_r($sections, true));
            
            // Add complete parsed sections to our debug file
            @file_put_contents($debug_file, "Parsed Sections (full content):\n", FILE_APPEND);
            foreach ($sections as $category => $content) {
                @file_put_contents($debug_file, "--- {$category} ---\n{$content}\n---END {$category}---\n\n", FILE_APPEND);
            }
            
            // Prepare results for JSON response
            $results = [
                'success' => true,
                'sections' => $sections,
                'selected_items' => $selected_items,
                'debug' => $debug_info
            ];
            
        } catch (Exception $e) {
            error_log("DEBUG - Exception: " . $e->getMessage());
            $results = [
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => isset($debug_info) ? $debug_info : null
            ];
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }
}

// Include the HTML template
include('header.php');
?>


<!-- Main content container -->
<div class="stars" id="stars"></div>
<div class="container">
    
            <div class="controls-row">
            <div class="secret-container">
                <label for="superSecret">Super Secret:</label>
                <input type="password" id="superSecret" placeholder="Enter the secret code">
            </div>
        </div>
        
        <div class="controls-row">
            <div class="location-container">
                <label for="location">Your Location:</label>
                <div class="location-flex">
                    <?php
                    // List of cities to randomly choose from
                    $default_cities = [
                        "San Francisco, CA",
                        "New York, NY",
                        "Tokyo, Japan",
                        "Paris, France",
                        "London, UK",
                        "Berlin, Germany",
                        "Moscow, Russia",
                        "Tel Aviv, Israel",
                        "Dubai, UAE",
                        "Kuala Lumpur, Malaysia",
                        "Bangkok, Thailand",
                        "Singapore",
                        "Sydney, Australia",
                        "Beijing, China",
                        "Hong Kong",
                        "Houghton, MI"
                    ];
                    
                    // Select a random city
                    $random_city = $default_cities[array_rand($default_cities)];
                    ?>
                    <input type="text" id="location" placeholder="e.g., San Francisco, CA" value="<?= htmlspecialchars($random_city) ?>">
                    <button id="detectLocation" class="location-button">Use My Location</button>
                </div>
            </div>
        </div>
    
    <button id="generateBtn">FIND COOL STUFF!!</button>

    <div class="categories">
        <?php foreach ($categories as $category => $items): ?>
        <div class="category-row">
            <div class="category" id="<?= $category ?>-category">
                <h2><?= strtoupper($category) ?></h2>
                <ul>
                    <?php foreach ($items as $item): ?>
                    <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="category-results" id="<?= $category ?>-results">
                <h2><?= strtoupper($category) ?> RECOMMENDATIONS</h2>
                <div class="result-content">Results will appear here after clicking the button...</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div id="results" style="display:none;"></div>
</div>

<?php
include('footer.php');
?>