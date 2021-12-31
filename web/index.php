<?php
require_once(__DIR__ . '/../.env.php');
require_once __DIR__ . '/vendor/autoload.php';

use MeiliSearch\Client;
use GuzzleHttp\Client as GuzzleClient;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] == '/send-feedback') {
    header('Content-Type: application/json');

    $email = $_POST['email'];
    $text = $_POST['text'];

    if (!$email || !$text) {
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

    $response = $client->request('POST', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage', [
        'form_params' => [
            'chat_id' => TELEGRAM_MY_CHAT_ID,
            'text' => $message,
        ],
        'timeout' => 10,
    ]);


    echo json_encode(['success' => true]);
    exit();
}


if (isset($_GET['problem']) &&  strlen($_GET['problem']) > 2) {
    try {
        $client = new Client(MEILISEARCH_CLIENT_URL);
        $index = $client->index(MEILISEARCH_APP_INDEX);
        $search = $index->search($_GET['problem']);
        $search_results = $search->getRaw();

        // echo print_r($search->getHits());
        // echo print_r($search->getRaw());
    } catch (Exception $e) {
        // echo $e->getMessage();
        // echo $e->getTraceAsString();
        $_GET['error'] =  $e->getMessage();
        // die('<pre>' . print_r($e->getMessage(), true) . '</pre>');
        // exit();
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
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="/assets/main.css">
</head>

<body class="bg-slate-200">
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <div class="max-w-3xl mx-auto flex flex-col items-center gap-8">
            <h1 class="font-bold text-2xl mt-7 text-center">Discover what companies are working on what problems</h1>

            <form action="" method="get" class="w-full flex justify-center">
                <div class="relative w-3/4">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <!-- Heroicon name: solid/search -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="search" minlength="2" name="problem" autocomplete="off" placeholder="water, energy, climate change, etc..." class="rounded-xl w-full text-center block pl-10">
                </div>
            </form>

            <?php if (isset($_GET['problem'])) { ?>
                <?php if (isset($_GET['error'])) { ?>
                    <p>Error: <?= $_GET['error']; ?></p>
                <?php }; ?>


                <?php if (isset($search_results) && $search_results['nbHits'] > 0) { ?>
                    <div class="place-self-center sm:place-self-start mb-20 mt-5">
                        <div class="m-2 text-gray-500"> <span class="font-semibold"> <?= $search_results['nbHits']; ?> </span> matches for: <span class="font-semibold"> <?= $_GET['problem']; ?> </span> </div>

                        <!-- result grid -->
                        <ul role="list" class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 ">
                            <?php foreach ($search_results['hits'] as $result) { ?>
                                <li class="hover:scale-105 col-span-1 flex flex-col text-center bg-white rounded-lg shadow divide-y divide-gray-200">
                                    <a href="#<?= $result['company_uid']; ?>">
                                        <div class="flex-1 flex flex-col p-8">
                                            <!-- <img class="w-32 h-32 flex-shrink-0 mx-auto rounded-full" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=4&w=256&h=256&q=60" alt=""> -->
                                            <h3 class="mt-6 text-gray-900 text-sm font-medium"><?= $result['name']; ?></h3>
                                            <dl class="mt-1 flex-grow flex flex-col justify-between">
                                                <dt class="sr-only">Symbol</dt>
                                                <dd class="text-gray-500 text-sm"><?= $result['symbol']; ?></dd>
                                                <!-- <dt class="sr-only">Role</dt>
                                                <dd class="mt-3">
                                                    <span class="px-2 py-1 text-green-800 text-xs font-medium bg-green-100 rounded-full">Admin</span>
                                                </dd> -->
                                            </dl>
                                        </div>
                                        <?php /*; ?>
                                        <div>
                                            <div class="-mt-px flex divide-x divide-gray-200">
                                                <div class="w-0 flex-1 flex">
                                                    <a href="mailto:janecooper@example.com" class="relative -mr-px w-0 flex-1 inline-flex items-center justify-center py-4 text-sm text-gray-700 font-medium border border-transparent rounded-bl-lg hover:text-gray-500">
                                                        <!-- Heroicon name: solid/mail -->
                                                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                                        </svg>
                                                        <span class="ml-3">Email</span>
                                                    </a>
                                                </div>
                                                <div class="-ml-px w-0 flex-1 flex">
                                                    <a href="tel:+1-202-555-0170" class="relative w-0 flex-1 inline-flex items-center justify-center py-4 text-sm text-gray-700 font-medium border border-transparent rounded-br-lg hover:text-gray-500">
                                                        <!-- Heroicon name: solid/phone -->
                                                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                                        </svg>
                                                        <span class="ml-3">Call</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        php; */ ?>
                                    </a>
                                </li>
                            <?php }; ?>
                        </ul>
                    </div>
                <?php } else { ?>
                    <div class="text-gray-500">
                        <p>No results found for: <?= $_GET['problem']; ?></p>
                    </div>
                <?php }; ?>

            <?php }; ?>
        </div>

        <!-- form for feedback -->
        <!-- <div class="absolute bottom-4">
            <p>Feedback</p>
            <form id="feedback" action="" method="POST" class="flex flex-col gap-2">
                <input type="email" name="email" placeholder="your@email.com" required>
                <textarea name="text" cols="30" rows="10" required placeholder="Your feedback here..."></textarea>
                <input type="submit" value="Submit">
            </form>
        </div> -->

        <a href="#" class="fixed left-5 -bottom-px p-2 hover:scale-105 rounded-t-md bg-white shadow-lg border-2 border-slate-400">Feedback</a>
    </main>
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