<?php
require_once(__DIR__ . '/../.env.php');
require_once __DIR__ . '/vendor/autoload.php';

use MeiliSearch\Client;
use GuzzleHttp\Client as GuzzleClient;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] == '/send-feedback') {
    header('Content-Type: application/json');

    $email = $_POST['email'];
    $text = $_POST['text'];

    if(!$email || !$text) {
        echo json_encode(['error' => 'email and text are required']);
        exit();
    }

    // send http request
    $client = new GuzzleClient();

    $message = "
User feedback for [companies aggregator project]
Email: {$email}

{$text}
    ";

    $response = $client->request('POST', 'https://api.telegram.org/bot'.TELEGRAM_BOT_TOKEN.'/sendMessage', [
        'form_params' => [
            'chat_id' => TELEGRAM_MY_CHAT_ID,
            'text' => $message,
        ],
        'timeout' => 10,
    ]);

    
    echo json_encode(['success' => true]);
    exit();
}

if (isset($_GET['problem'])) {
    try {
        $client = new Client(MEILISEARCH_CLIENT_URL);
        $index = $client->index(MEILISEARCH_APP_INDEX);
        $search = $index->search($_GET['problem']);
        $search_results = $search->getRaw();

        // echo print_r($search->getHits());
        echo print_r($search->getRaw());
    } catch (Exception $e) {
        // echo $e->getMessage();
        // echo $e->getTraceAsString();
        $_GET['error'] =  $e->getMessage();
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover companies</title>
    <link rel="stylesheet" href="/assets/main.css">
</head>

<body class="bg-red">
    <h1>Discover what companies are working on what problems</h1>

    <form action="" method="get">
        <input type="search" minlength="2" name="problem" autocomplete="off" placeholder="water, energy, climate change, etc...">
    </form>

    <?php if (isset($_GET['problem'])) { ?>
        <?php if (isset($_GET['error'])) { ?>
            <p>Error: <?= $_GET['error']; ?></p>
        <?php }; ?>
        <div>
            <p>Searching for: <?= $_GET['problem']; ?></p>
        </div>

        <?php if ($search_results['nbHits'] > 0) { ?>
            <div>
                <p><?= $search_results['nbHits']; ?> matches</p>
                <ul>
                    <?php foreach ($search_results['hits'] as $result) { ?>
                        <li>
                            <a href="#<?= $result['company_uid']; ?>">
                                <p>
                                    <?= $result['name']; ?>
                                </p>
                                <p>
                                    <?= $result['symbol']; ?>
                                </p>
                            </a>
                        </li>
                    <?php }; ?>
                </ul>
            </div>
        <?php } else { ?>
            <div>
                <p>No results found</p>
            </div>
        <?php }; ?>

    <?php }; ?>

    <!-- form for feedback -->
    <div>
        <p>Feedback</p>
        <form id="feedback" action="" method="POST">
            <input type="email" name="email" placeholder="your@email.com" required>
            <textarea name="text" cols="30" rows="10" required></textarea>
            <input type="submit" value="Submit">
        </form>
    </div>
    <script>
        document.querySelector('form#feedback').addEventListener('submit', async function(e) {
            e.preventDefault();
            // send request
            let request = await fetch('/send-feedback', {
                method: 'POST',
                body: new FormData(this)
            });

            console.log(request);
        })
    </script>
</body>

</html>