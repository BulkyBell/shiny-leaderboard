<?php
// URL of the page to scrape
$url = "https://forums.pokemmo.com/index.php?/topic/159105-team-lgnds-ot-shiny-board/";

// Initialize cURL session
$ch = curl_init();

// Set the URL and other necessary options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request
$response = curl_exec($ch);

// Close cURL session
curl_close($ch);

// Check if the response was successful
if ($response === false) {
    die("Failed to fetch the webpage.");
}

// Load the HTML response into a DOMDocument
$dom = new DOMDocument();
libxml_use_internal_errors(true); // Suppress HTML parsing errors
$dom->loadHTML($response);
libxml_clear_errors();

// Create a new XPath object
$xpath = new DOMXPath($dom);

// Find all <p> tags
$pTags = $xpath->query("//p");

// Exclusion lists
$usernameExclusionList = ["By JetxLore", "Team LEGEND's OT Shiny Board"];
$imageUrlExclusionList = [
    "https://forums.pokemmo.com/uploads/monthly_2022_06/secret_shiny_particle.png.11a9039aa8014d4afbd7e50a542b631c.png",
    "https://forums.pokemmo.com/uploads/monthly_2022_10/image.png.bbfb8af0def75a91b364007b219e736c.png",
    "https://archives.bulbagarden.net/media/upload/e/eb/Bag_Safari_Ball_Sprite.png",
    "https://forums.pokemmo.com/uploads/monthly_2024_06/shinywars2.thumb.png.18f54273d58f7b6ed22e8e81c023e422.png"
];

// Lookup list for special cases like 'x4'
$usernameLookupList = [
    "x2" => 1,
    "x3" => 2,
    "x4" => 3
];

$users = [];
$imageUrls = [];
$currentUsername = "";
$lastValidUsername = "";

// Loop through each <p> tag
foreach ($pTags as $pTag) {
    // Check if the <p> tag contains a username
    $usernameNode = $xpath->query(".//span/strong", $pTag);
    if ($usernameNode->length > 0) {
        $potentialUsername = $usernameNode->item(0)->textContent;
        
        if (isset($usernameLookupList[$potentialUsername])) {
            if ($lastValidUsername != "") {
                $users[$lastValidUsername]['imageCount'] += $usernameLookupList[$potentialUsername];
            }
        } elseif (!in_array($potentialUsername, $usernameExclusionList)) {
            $currentUsername = $potentialUsername;
            $lastValidUsername = $currentUsername;
            $users[$currentUsername] = [
                'imageCount' => 0,
                'images' => []
            ];
        } else {
            // Do not reset current username if it's in the exclusion list
            $currentUsername = $lastValidUsername;
        }
    }

    // If there's a current username, look for images in the current <p> tag
    if ($currentUsername != "") {
        $images = $xpath->query(".//img", $pTag);

        // Get all image URLs
        foreach ($images as $image) {
            $imgUrl = $image->getAttribute("src");
            if (!in_array($imgUrl, $imageUrlExclusionList)) {
                $imageUrls[] = $imgUrl;
                $users[$currentUsername]['images'][] = $imgUrl;
                $users[$currentUsername]['imageCount']++;
            }
        }
    }
}

// Display the results
echo "<h1>Team LGNDS OT Shiny Board</h1>";
echo "<ul>";
foreach ($users as $username => $data) {
    echo "<li><strong>$username</strong>: {$data['imageCount']} images";
    echo "<ul>";
    foreach ($data['images'] as $url) {
        echo "<li>$url</li>";
    }
    echo "</ul></li>";
}
echo "</ul>";

echo "<h2>All Image URLs (for debugging)</h2>";
echo "<ul>";
foreach ($imageUrls as $url) {
    echo "<li>$url</li>";
}
echo "</ul>";
?>
