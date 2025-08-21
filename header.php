<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>COOL STUFF Generator</title>
    <style>
        body {
            background-color: #000;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #00ff00;
            padding: 15px;
            position: relative;
            z-index: 1;
        }

        .categories {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .category-row {
            display: flex;
            flex-direction: row;
            gap: 15px;
            margin-bottom: 15px;
            width: 100%;
        }
        
        .category {
            flex: 1;
            border: 1px solid #00ff00;
            padding: 10px;
            background-color: rgba(0, 20, 0, 0.5);
        }
        
        .category-results {
            flex: 2;
            border: 1px solid #00ff00;
            padding: 10px;
            background-color: rgba(0, 20, 0, 0.3);
            white-space: pre-wrap;
            font-size: 0.9rem;
            display: none;
        }
        
        .category-results.active {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        /* Responsive design for mobile devices */
        @media screen and (max-width: 768px) {
            .category-row {
                flex-direction: column;
                gap: 10px;
                margin-bottom: 25px;
            }
            
            .category {
                flex: none;
                width: 100%;
            }
            
            .category-results {
                flex: none;
                width: 100%;
                margin-top: 5px;
            }
            
            .container {
                padding: 10px;
                max-width: 100%;
            }
            
            body {
                padding: 10px;
                font-size: 14px;
            }
            
            button {
                padding: 12px;
                font-size: 1.2rem;
            }
            
            .controls-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .location-container, .secret-container {
                width: 100%;
            }
            
            .location-flex {
                flex-direction: column;
                gap: 5px;
                align-items: stretch;
            }
            
            .location-button {
                width: 100%;
                margin-top: 5px;
                height: auto;
                padding: 8px;
            }
            
            .location-container input, .secret-container input {
                height: auto;
                padding: 8px;
            }
            
            /* Improve readability of business entries on mobile */
            .category-results h2 {
                font-size: 1rem;
                margin-bottom: 10px;
            }
            
            /* Add more space between business entries on mobile */
            [style*="margin-top: 15px; border-top: 1px dotted"] {
                margin-top: 20px !important;
                padding-top: 15px !important;
            }
        }

        .category h2 {
            margin-top: 0;
            margin-bottom: 6px;
            color: #00ff00;
            border-bottom: 1px solid #00ff00;
            padding-bottom: 4px;
            font-size: 1.1rem;
        }
        
        .category-results h2 {
            margin-top: 0;
            margin-bottom: 6px;
            color: #00ff00;
            border-bottom: 1px solid #00ff00;
            padding-bottom: 4px;
            font-size: 1.1rem;
            width: 100%;
        }
        
        .result-content {
            width: 100%;
            margin-top: 0;
            align-self: flex-start;
        }

        ul {
            list-style-type: none;
            padding-left: 0;
            margin-top: 6px;
            margin-bottom: 6px;
        }

        li {
            margin-bottom: 5px;
            position: relative;
            padding-left: 16px;
            font-size: 0.9rem;
        }

        li::before {
            content: ">";
            position: absolute;
            left: 0;
            color: #00ff00;
        }

        button {
            display: block;
            width: 100%;
            padding: 15px;
            font-size: 1.5rem;
            background-color: #000;
            color: #00ff00;
            border: 2px solid #00ff00;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 15px 0 20px;
            transition: all 0.3s;
        }

        button:hover {
            background-color: #00ff00;
            color: #000;
        }

        button:active {
            transform: scale(0.98);
        }

        #results {
            border: 1px solid #00ff00;
            padding: 20px;
            min-height: 200px;
            background-color: rgba(0, 20, 0, 0.5);
            display: none;
            white-space: pre-wrap;
        }

        /* Asteroids-like floating dots */
        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .star {
            position: absolute;
            background-color: #00ff00;
            width: 2px;
            height: 2px;
            border-radius: 50%;
            opacity: 0.5;
        }

        /* API key and location input */
        .controls-row {
            display: flex;
            gap: 15px;
            margin: 5px 0 15px;
        }
        
        .location-container, .secret-container {
            flex: 1;
            border: 1px solid #00ff00;
            padding: 8px;
        }

        .location-container label, .secret-container label {
            display: block;
            margin-bottom: 4px;
            font-size: 0.9rem;
        }

        .location-container input, .secret-container input {
            width: 100%;
            padding: 4px 6px;
            background-color: #000;
            border: 1px solid #00ff00;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            height: 24px;
        }
        
        .location-flex {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .location-button {
            background-color: #000;
            color: #00ff00;
            border: 1px solid #00ff00;
            padding: 4px 8px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
            font-size: 0.8rem;
            height: 24px;
        }
        
        .location-button:hover {
            background-color: #00ff00;
            color: #000;
        }
        
        /* Selected category item and loading state */
        .category li {
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .category li:hover {
            color: #ffff88;
        }
        
        .category li.selected {
            color: #ffff00;
            font-weight: bold;
        }
        
        .loading {
            color: #00ffff;
            font-style: italic;
            margin: 10px 0;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
        
        .error {
            color: #ff5555;
            margin: 10px 0;
        }
        
        /* Better business entry styling */
        .business-entry {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dotted #003300;
        }
        
        .business-entry:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>