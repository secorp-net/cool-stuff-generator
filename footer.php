    <script>
        // Object to track previous recommendations for each activity
        const previousRecommendations = {};
        
        // Client-side functions for star animations and location detection
        document.addEventListener('DOMContentLoaded', function() {
            const starsContainer = document.getElementById('stars');
            const numberOfStars = 100;
            
            for (let i = 0; i < numberOfStars; i++) {
                const star = document.createElement('div');
                star.classList.add('star');
                star.style.left = `${Math.random() * 100}%`;
                star.style.top = `${Math.random() * 100}%`;
                starsContainer.appendChild(star);
            }
            
            // Make stars move slowly
            animateStars();
            
            // Set up location detection
            document.getElementById('detectLocation').addEventListener('click', detectUserLocation);
            
            // Set up generate button
            document.getElementById('generateBtn').addEventListener('click', generateRecommendations);
            
            // Set up click handlers for category items
            setupCategoryItemClicks();
            
            // Check for super-secret parameter in URL
            checkForSuperSecretParam();
        });
        
        // Function to set up click handlers for category items
        function setupCategoryItemClicks() {
            // Get all category lists
            const categories = document.querySelectorAll('.category');
            
            categories.forEach(category => {
                // Get the category ID (work, sports, social, food)
                const categoryId = category.id.replace('-category', '');
                
                // Get all items in this category
                const items = category.querySelectorAll('li');
                
                // Add click handler to each item
                items.forEach(item => {
                    item.addEventListener('click', function() {
                        // Get the text of this item
                        const activityText = item.textContent.trim();
                        
                        // Only proceed if the generate button has been clicked at least once
                        const categoryResults = document.getElementById(`${categoryId}-results`);
                        if (!categoryResults.classList.contains('active')) {
                            alert('Click "FIND COOL STUFF!!" first to generate initial recommendations');
                            return;
                        }
                        
                        // Update selected items
                        updateSelectedItem(categoryId, item);
                        
                        // Generate a single recommendation for this item
                        generateSingleItemRecommendation(categoryId, activityText);
                    });
                });
            });
        }
        
        // Update selected item in a category
        function updateSelectedItem(categoryId, selectedItem) {
            // Get all items in this category
            const categoryElement = document.getElementById(`${categoryId}-category`);
            const items = categoryElement.querySelectorAll('li');
            
            // Remove selection from all items
            items.forEach(item => {
                item.classList.remove('selected');
                item.style.color = '#00ff00'; // Reset to default color
                item.style.fontWeight = 'normal';
            });
            
            // Add selection to clicked item
            selectedItem.classList.add('selected');
            selectedItem.style.color = '#ffff00'; // Highlight in yellow
            selectedItem.style.fontWeight = 'bold';
        }
        
        // Function to generate a single recommendation for a specific activity
        function generateSingleItemRecommendation(category, activity) {
            // Check if the super secret field is filled
            const superSecret = document.getElementById('superSecret').value;
            if (!superSecret) {
                alert('Please enter the Super Secret value');
                return;
            }
            
            // Get user's location
            const location = document.getElementById('location').value || "";
            if (!location.trim()) {
                alert('Please enter your location or click "Use My Location"');
                return;
            }
            
            // Get the results container for this category
            const resultElement = document.getElementById(`${category}-results`);
            const contentContainer = resultElement.querySelector('.result-content');
            
            // Show loading message
            contentContainer.innerHTML = '<div class="loading">Generating recommendation for "' + activity + '"...</div>';
            
            // Get previously recommended places for this activity
            const activityKey = `${category}-${activity}`;
            if (!previousRecommendations[activityKey]) {
                previousRecommendations[activityKey] = [];
            }
            
            // Make AJAX request to get a single recommendation
            const formData = new FormData();
            formData.append('action', 'generate_single_item');
            formData.append('super_secret', superSecret);
            formData.append('location', location);
            formData.append('category', category);
            formData.append('activity', activity);
            formData.append('previous_recommendations', JSON.stringify(previousRecommendations[activityKey]));
            
            console.log(`Sending request for ${category} - ${activity}`);
            console.log(`Previous recommendations for this activity:`, previousRecommendations[activityKey]);
            
            // Get the current script path to ensure we're posting to the right URL
            const scriptPath = window.location.pathname;
            fetch(scriptPath, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Log the full response for debugging
                console.log("Server response:", data);
                
                if (data.success) {
                    console.log(`Response sections:`, data.sections);
                    
                    // Get the content for this category
                    const categoryContent = data.sections[category];
                    console.log(`Category content for ${category}:`, categoryContent);
                    
                    if (categoryContent) {
                        // Format the content with colored business names
                        const formattedContent = formatContentWithColoredBusinessNames(categoryContent);
                        
                        // Create content element
                        const contentElement = document.createElement('div');
                        contentElement.style.whiteSpace = 'pre-wrap';
                        contentElement.style.margin = '0';
                        contentElement.style.fontFamily = 'inherit';
                        contentElement.style.fontSize = 'inherit';
                        contentElement.innerHTML = formattedContent;
                        
                        // Clear and update the content container
                        contentContainer.innerHTML = '';
                        contentContainer.appendChild(contentElement);
                        
                        // Extract and store the business name for future reference
                        const businessName = extractBusinessName(categoryContent);
                        if (businessName) {
                            const activityKey = `${category}-${activity}`;
                            if (!previousRecommendations[activityKey]) {
                                previousRecommendations[activityKey] = [];
                            }
                            previousRecommendations[activityKey].push(businessName);
                            console.log(`Added "${businessName}" to previous recommendations for ${activity}`);
                        }
                        
                        // Debug log
                        console.log(`Generated recommendation for ${category} - ${activity}:`, categoryContent);
                    } else {
                        contentContainer.innerHTML = `<div class="error">No recommendations found for "${activity}"</div>`;
                    }
                } else {
                    // Show error message
                    contentContainer.innerHTML = `<div class="error">Error: ${data.error || 'Unknown error occurred'}</div>`;
                }
            })
            .catch(error => {
                // Show error message
                contentContainer.innerHTML = `<div class="error">Error: ${error.message || 'Unknown error occurred'}</div>`;
            });
        }
        
        // Function to check for super-secret URL parameter and fill the field
        function checkForSuperSecretParam() {
            const urlParams = new URLSearchParams(window.location.search);
            const superSecretParam = urlParams.get('super-secret');
            
            if (superSecretParam) {
                const superSecretField = document.getElementById('superSecret');
                if (superSecretField) {
                    superSecretField.value = superSecretParam;
                    console.log("Super Secret field auto-filled from URL parameter");
                }
            }
        }
        
        // Shareable link section removed - URL parameter functionality still works
        
        // Function to extract business name from a recommendation
        function extractBusinessName(content) {
            const businessNameRegex = /BUSINESS NAME:\s*(.*?)(\n|$)/;
            const match = businessNameRegex.exec(content);
            if (match && match[1]) {
                return match[1].trim();
            }
            return null;
        }
        
        // Function to format content with colored business names and make URLs clickable
        function formatContentWithColoredBusinessNames(content) {
            // Escape HTML
            let escapedContent = content
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            
            // Helper function to detect and process URLs with or without the http(s):// prefix
            function processUrls(text) {
                // First handle URLs with http:// or https://
                text = text.replace(
                    /\b(https?:\/\/[^\s,]+)/gi,
                    '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:#88ffff; text-decoration: underline;">$1</a>'
                );
                
                // Then handle URLs without a protocol prefix (www.domain.com)
                // Don't match partial strings like "at www.somesite.com"
                text = text.replace(
                    /\b(www\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+(?:\/[^\s,]*)?)\b/gi,
                    '<a href="http://$1" target="_blank" rel="noopener noreferrer" style="color:#88ffff; text-decoration: underline;">$1</a>'
                );
                
                return text;
            }
            
            // Store extracted website URLs for linking business names
            const businessWebsites = {};
            
            // First pass: Extract website URLs with improved regex
            // This pre-processes the content to find WEBSITE: fields and their associated business names
            // Using a more robust regex that can handle both numbered and unnumbered entries
            const websiteRegex = /(?:(\d+\.\s+))?BUSINESS NAME:\s*(.*?)[\r\n][\s\S]*?WEBSITE:\s*(.*?)(?:[\r\n]|$)/gi;
            let websiteMatch;
            
            // Log what we're about to parse for debugging
            console.log("Content to parse for business names:", escapedContent);
            
            while ((websiteMatch = websiteRegex.exec(escapedContent)) !== null) {
                // websiteMatch[1] might be undefined for unnumbered entries
                const itemNumber = websiteMatch[1] ? websiteMatch[1].trim() : ""; 
                const businessName = websiteMatch[2].trim();
                const website = websiteMatch[3].trim();
                
                console.log("Found business:", {itemNumber, businessName, website});
                
                // Store the website URL for this business name if it's valid
                if (website && website.toLowerCase() !== 'n/a' && website.toLowerCase() !== 'none' && 
                    website !== '-' && website.toLowerCase() !== 'not available') {
                    // Create a key from item number to uniquely identify this business
                    businessWebsites[itemNumber + businessName] = website;
                }
            }
            
            // Function to process business names and make them clickable if they have a website
            function processBusinessName(match, businessNamePart, ending) {
                // Extract the business name (after "BUSINESS NAME: ")
                const namePrefix = "BUSINESS NAME: ";
                const nameStartIndex = businessNamePart.indexOf(namePrefix) + namePrefix.length;
                const businessName = businessNamePart.substring(nameStartIndex);
                
                // Debug - log what we're processing
                console.log("Processing business name:", businessName);
                
                // Don't auto-detect URLs in business names - this was causing issues
                // Simply format the business name in white/bold for better visibility
                return `<span style="color: #ffffff; font-weight: bold;">${businessNamePart}</span>${ending}`;
            }
            
            // Modified approach - first preserve the numbered entries (1., 2., etc.) 
            // before processing business names
            escapedContent = escapedContent.replace(
                /^(\d+\.\s+)(BUSINESS NAME:.*?)($|\n)/gm, 
                function(match, numPrefix, businessNamePart, ending) {
                    // Keep the number prefix intact
                    return numPrefix + processBusinessName(match, businessNamePart, ending);
                }
            );
            
            // Then handle any remaining unnumbered business entries
            escapedContent = escapedContent.replace(
                /^(BUSINESS NAME:.*?)($|\n)/gm, 
                processBusinessName
            );
            
            // Add extra spacing between numbered items and wrap in business-entry div
            // First handle any items at the beginning of the string (no preceding newline)
            escapedContent = escapedContent.replace(
                /^(\d+\.\s+BUSINESS NAME:)/,
                '<div class="business-entry">$1'
            );
            
            // Then handle items with a preceding newline
            escapedContent = escapedContent.replace(
                /\n(\d+\.\s+BUSINESS NAME:)/g,
                '\n</div><div class="business-entry">$1'
            );
            
            // Close the last business entry div
            escapedContent += '</div>';
            
            // Enhance DESCRIPTION, ADDRESS, and NOTE labels and make URLs clickable
            // Process DESCRIPTION line to make any embedded URLs clickable
            escapedContent = escapedContent.replace(
                /(DESCRIPTION:.*?)($|\n)/g, 
                function(match, descPart, ending) {
                    // Make the label colored
                    let processedDesc = descPart.replace(
                        /DESCRIPTION:/, 
                        '<span style="color: #88ff88;">DESCRIPTION:</span>'
                    );
                    
                    // Process URLs using our helper function
                    processedDesc = processUrls(processedDesc);
                    
                    return processedDesc + ending;
                }
            );
            
            // Process WEBSITE line and make it a clickable link
            escapedContent = escapedContent.replace(
                /(WEBSITE:.*?)($|\n)/g, 
                function(match, websitePart, ending) {
                    // First make the label colored
                    let processedWebsite = websitePart.replace(
                        /WEBSITE:/, 
                        '<span style="color: #88ff88;">WEBSITE:</span>'
                    );
                    
                    // Extract the website URL (after "WEBSITE: ")
                    const websitePrefix = '<span style="color: #88ff88;">WEBSITE:</span> ';
                    const websiteStartIndex = processedWebsite.indexOf(websitePrefix) + websitePrefix.length;
                    const websiteUrl = processedWebsite.substring(websiteStartIndex).trim();
                    
                    if (websiteUrl && websiteUrl.toLowerCase() !== 'n/a' && websiteUrl.toLowerCase() !== 'none' && 
                        websiteUrl !== '-' && websiteUrl.toLowerCase() !== 'not available') {
                        let formattedUrl = websiteUrl;
                        let fullUrl = websiteUrl;
                        
                        // Check if the URL has a protocol, if not add http://
                        if (!/^https?:\/\//i.test(websiteUrl)) {
                            fullUrl = 'http://' + websiteUrl;
                        }
                        
                        // Create a clickable link
                        processedWebsite = websitePrefix + 
                            `<a href="${fullUrl}" target="_blank" rel="noopener noreferrer" style="color:#ffff88; font-weight: bold; text-decoration: underline;">${formattedUrl}</a>`;
                    }
                    
                    return processedWebsite + ending;
                }
            );
            
            // Process ADDRESS line to make any embedded URLs clickable
            escapedContent = escapedContent.replace(
                /(ADDRESS:.*?)($|\n)/g, 
                function(match, addressPart, ending) {
                    // Make the label colored
                    let processedAddress = addressPart.replace(
                        /ADDRESS:/, 
                        '<span style="color: #88ff88;">ADDRESS:</span>'
                    );
                    
                    // Process URLs using our helper function
                    processedAddress = processUrls(processedAddress);
                    
                    return processedAddress + ending;
                }
            );
            
            // Process NOTE line to make any embedded URLs clickable
            escapedContent = escapedContent.replace(
                /(NOTE:.*?)($|\n)/g, 
                function(match, notePart, ending) {
                    // Make the label colored
                    let processedNote = notePart.replace(
                        /NOTE:/, 
                        '<span style="color: #88ff88;">NOTE:</span>'
                    );
                    
                    // Process URLs using our helper function
                    processedNote = processUrls(processedNote);
                    
                    return processedNote + ending;
                }
            );
            
            return escapedContent;
        }

        function animateStars() {
            const stars = document.querySelectorAll('.star');
            
            stars.forEach(star => {
                let posX = parseFloat(star.style.left);
                let posY = parseFloat(star.style.top);
                
                setInterval(() => {
                    posX += (Math.random() - 0.5) * 0.1;
                    posY += (Math.random() - 0.5) * 0.1;
                    
                    if (posX < 0) posX = 100;
                    if (posX > 100) posX = 0;
                    if (posY < 0) posY = 100;
                    if (posY > 100) posY = 0;
                    
                    star.style.left = `${posX}%`;
                    star.style.top = `${posY}%`;
                }, 100);
            });
        }
        
        // Function to detect user's location
        function detectUserLocation() {
            const locationInput = document.getElementById('location');
            const detectButton = document.getElementById('detectLocation');
            
            // Save original button text
            const originalText = detectButton.textContent;
            detectButton.textContent = "Detecting...";
            detectButton.disabled = true;
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    // Success callback
                    async (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        try {
                            // Use reverse geocoding to get location name
                            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=10`);
                            const data = await response.json();
                            
                            // Extract city and state/country from response
                            let location = "";
                            
                            if (data.address) {
                                const address = data.address;
                                const city = address.city || address.town || address.village || address.hamlet;
                                const state = address.state || address.county;
                                const country = address.country;
                                
                                if (city && state) {
                                    location = `${city}, ${state}`;
                                } else if (city && country) {
                                    location = `${city}, ${country}`;
                                } else if (state && country) {
                                    location = `${state}, ${country}`;
                                } else if (city) {
                                    location = city;
                                } else if (state) {
                                    location = state;
                                } else if (country) {
                                    location = country;
                                } else {
                                    location = "Unknown location";
                                }
                            } else {
                                location = "Unknown location";
                            }
                            
                            // Update the location input
                            locationInput.value = location;
                            
                        } catch (error) {
                            console.error("Error getting location name:", error);
                            locationInput.value = `${lat.toFixed(2)}, ${lng.toFixed(2)}`;
                        }
                        
                        // Reset button
                        detectButton.textContent = originalText;
                        detectButton.disabled = false;
                    },
                    // Error callback
                    (error) => {
                        console.error("Error getting location:", error);
                        alert("Could not detect your location. Please enter it manually.");
                        detectButton.textContent = originalText;
                        detectButton.disabled = false;
                    }
                );
            } else {
                alert("Geolocation is not supported by this browser. Please enter your location manually.");
                detectButton.textContent = originalText;
                detectButton.disabled = false;
            }
        }
        
        // Function to generate recommendations
        function generateRecommendations() {
            // Check if the super secret field is filled
            const superSecret = document.getElementById('superSecret').value;
            if (!superSecret) {
                alert('Please enter the Super Secret value');
                return;
            }

            // Get user's location (using whatever is currently in the input field as default)
            const location = document.getElementById('location').value || "";
            if (!location.trim()) {
                alert('Please enter your location or click "Use My Location"');
                return;
            }

            // Reset any previous results
            document.querySelectorAll('.category-results').forEach(el => {
                const contentContainer = el.querySelector('.result-content');
                if (contentContainer) {
                    contentContainer.textContent = 'Generating recommendations...';
                }
                el.classList.add('active');
            });
            
            // Hide results container
            document.getElementById('results').style.display = 'none';
            
            // Make an AJAX request to the PHP backend
            const formData = new FormData();
            formData.append('action', 'generate');
            formData.append('super_secret', superSecret);
            formData.append('location', location);
            
            // Get the current script path to ensure we're posting to the right URL
            const scriptPath = window.location.pathname;
            fetch(scriptPath, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Debug logging
                console.log("=== DEBUG INFORMATION ===");
                if (data.debug) {
                    console.log("Prompt used:");
                    console.log(data.debug.debug_prompt);
                    console.log("Raw API Response:");
                    console.log(data.debug.debug_raw_response);
                }
                console.log("Full response data:", data);
                console.log("=== END DEBUG ===");
                
                // Shareable link functionality removed
                
                if (data.success) {
                    // Show all category result containers
                    document.querySelectorAll('.category-results').forEach(el => {
                        el.classList.add('active');
                    });
                    
                    // Display selected items (highlight them in the UI)
                    for (const [category, items] of Object.entries(data.selected_items)) {
                        const categoryElement = document.getElementById(`${category}-category`);
                        if (categoryElement) {
                            const listItems = categoryElement.querySelectorAll('li');
                            
                            // Reset all items
                            listItems.forEach(item => {
                                item.style.color = '#00ff00';
                                item.style.fontWeight = 'normal';
                            });
                            
                            // Highlight selected items
                            items.forEach(selectedItem => {
                                for (const li of listItems) {
                                    if (li.textContent === selectedItem) {
                                        li.style.color = '#ffff00';
                                        li.style.fontWeight = 'bold';
                                        break;
                                    }
                                }
                            });
                        }
                    }
                    
                                                        // Display results in their respective category areas
                    for (const [category, content] of Object.entries(data.sections)) {
                        const resultElement = document.getElementById(`${category}-results`);
                        if (resultElement) {
                            const contentContainer = resultElement.querySelector('.result-content');
                            if (contentContainer) {
                                // Format the content nicely with preserved whitespace
                                contentContainer.innerHTML = '';
                                
                                // Format the content with colored business names
                                console.log("Original content before formatting:", content);
                                const formattedContent = formatContentWithColoredBusinessNames(content);
                                
                                // Add a container element for the formatted content
                                const contentElement = document.createElement('div');
                                contentElement.style.whiteSpace = 'pre-wrap';
                                contentElement.style.margin = '0';
                                contentElement.style.fontFamily = 'inherit';
                                contentElement.style.fontSize = 'inherit';
                                contentElement.innerHTML = formattedContent;
                                
                                contentContainer.appendChild(contentElement);
                                
                                // Enhanced debug - log full content details 
                                console.log(`${category} content (full):`, content);
                                // Check if content includes business name entries
                                const hasBusinessNames = content.includes("BUSINESS NAME:");
                                console.log(`${category} has business names: ${hasBusinessNames}`);
                            }
                        }
                    }
                    
                    // Handle categories without specific content in the response
                    for (const category of ['work', 'sports', 'social', 'food']) {
                        if (!data.sections[category]) {
                            const resultElement = document.getElementById(`${category}-results`);
                            const contentContainer = resultElement.querySelector('.result-content');
                            if (contentContainer) {
                                contentContainer.textContent = 'No specific recommendations found for this category. Check other categories for potential results.';
                            }
                        }
                    }
                } else {
                    // Show error in all category results
                    document.querySelectorAll('.category-results').forEach(el => {
                        el.classList.add('active');
                        const contentContainer = el.querySelector('.result-content');
                        if (contentContainer) {
                            contentContainer.textContent = `Error: ${data.error}`;
                        }
                    });
                }
            })
            .catch(error => {
                // Debug logging for errors
                console.log("=== DEBUG ERROR ===");
                console.error("Error:", error);
                console.log("=== END DEBUG ERROR ===");
                
                // Show error in all category results
                document.querySelectorAll('.category-results').forEach(el => {
                    el.classList.add('active');
                    const contentContainer = el.querySelector('.result-content');
                    if (contentContainer) {
                        contentContainer.textContent = `Error: ${error.message}`;
                    }
                });
            });
        }
    </script>
</body>
</html>