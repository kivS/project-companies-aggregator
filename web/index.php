<?php
require_once(__DIR__ . '/../.env.php');
require_once __DIR__ . '/vendor/autoload.php';

if (SENTRY_DSN) {
    \Sentry\init(['dsn' => SENTRY_DSN]);
}


use MeiliSearch\Client;
use GuzzleHttp\Client as GuzzleClient;

$db = new SQLite3(DB_PATH);


if ($_SERVER['DOCUMENT_URI'] == '/company-detail' && isset($_GET['uid'])) {
    header('Content-Type: application/json');

    // get company from db
    $stmt = $db->prepare(
        'SELECT 
            uid,
            clean_name as name, 
            symbol, 
            ipo_year,  
            sector,
            country,
            industry,
            website_url,
            media_links
        FROM 
            stonks 
        WHERE uid = :uid'
    );
    $stmt->bindValue(':uid', $_GET['uid']);
    $result = $stmt->execute();
    $company = $result->fetchArray(SQLITE3_ASSOC);

    if (!$company) {
        http_response_code(404);
        echo json_encode(['error' => 'Company not found']);
        exit();
    }

    echo json_encode($company);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] == '/send-feedback') {
    header('Content-Type: application/json');
    header('Accept: application/json');

    $email = $_POST['email'];
    $text = $_POST['text'];

    if (!$email || !$text) {
        echo json_encode(['error' => 'email and text are required']);
        exit();
    }

    // send http request
    $client = new GuzzleClient();

    $message = "
User feedback for [" . PROJECT_URL . "]
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


if (isset($_GET['problem']) &&  strlen($_GET['problem']) > 1) {
    try {
        $client = new Client(MEILISEARCH_CLIENT_URL, MEILISEARCH_API_KEY);
        $index = $client->index(MEILISEARCH_APP_INDEX);
        $search = $index->search($_GET['problem'], ['limit' => 18, 'sort' => ['symbol:asc'], 'attributesToHighlight' => ['tags'], 'facetsDistribution' => ['tags'], 'matches' => false, 'attributesToRetrieve' => ['company_uid', 'name', 'symbol']]);
        $search_results = $search->getRaw();

        // header('Content-Type: application/json'); // DEBUG
        // echo json_encode($search_results, JSON_PRETTY_PRINT); // DEBUG
        // exit(); // DEBUG



        $pluralized_match_number = $search_results['nbHits'] == 1 ? 'company' : 'companies';

        // echo print_r($search->getHits());
        // echo print_r($search->getRaw());

        // store searched terms in db into user_searches
        $stmt = $db->prepare(
            'INSERT INTO user_searches (problem, user_ip, user_agent, nb_hits, created_at) 
            VALUES (:problem, :user_ip, :user_agent, :nb_hits, :created_at)'
        );
        $stmt->bindValue(':problem', $_GET['problem']);
        $stmt->bindValue(':user_ip', $_SERVER['REMOTE_ADDR']);
        $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        $stmt->bindValue(':nb_hits', $search_results['nbHits']);
        $stmt->bindValue(':created_at', (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:sP'));
        $stmt->execute();
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
    <meta name="Description" CONTENT="Discover companies by the problems they're working on">
    <title>Discover companies by the problems they're working on</title>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="/assets/main.css">
    <script defer src="/assets/alpinejs@3.8.0.js"></script>
    <?php if (USE_NINJA_ANALYTICS) { ?>
        <script defer data-domain="problemsolvers.kiv.software" src="https://ninja.kiv.software/js/plausible.js"></script>
    <?php }; ?>
</head>

<body class="bg-slate-200" x-data="{ feedbackModalShow: false, companyDetailModalShow: false, company: {}, companyHighlights: [], companyHighlightsModalShow: false }">
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <div class="max-w-3xl mx-auto flex flex-col items-center gap-8">
            <h1 class="font-bold text-2xl mt-7 text-center">
                <a href="/" tabindex="-1">
                    Discover companies by the problems they're working on
                </a>
            </h1>
            </a>

            <form action="" method="get" class="w-full flex justify-center">
                <div class="relative w-3/4">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <!-- Heroicon name: solid/search -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="search" x-init="$el.focus()" tabindex="0" minlength="2" name="problem" required autocomplete="off" placeholder="electric cars, cancer, solar, etc..." class="rounded-xl w-full text-center block pl-10">
                </div>
            </form>

            <?php if (isset($_GET['problem'])) { ?>
                <?php if (isset($_GET['error'])) { ?>
                    <p>Error: <?= $_GET['error']; ?></p>
                <?php }; ?>


                <?php if (isset($search_results) && $search_results['nbHits'] > 0) { ?>
                    <div class="place-self-center sm:place-self-start mb-20 mt-5">
                        <div class="m-2 text-gray-500"> <span class="font-semibold"> <?= $search_results['nbHits']; ?> </span> <?= $pluralized_match_number; ?> working on <span class="font-semibold"> <?= $_GET['problem']; ?> </span> </div>

                        <!-- result grid -->
                        <ul role="list" class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 ">
                            <?php foreach ($search_results['hits'] as $result) { ?>
                                <li class="relative hover:scale-105 col-span-1 flex flex-col text-center bg-white rounded-lg shadow divide-y divide-gray-200">

                                    <!-- highlight matches -->
                                    <?php

                                    $highlights = [];

                                    foreach ($result['_formatted']['tags'] as $tag) {

                                        if (strpos($tag, '<em>') === false) {
                                            continue;
                                        }

                                        // replace 'em' tag with 'mark' tag
                                        $tag = str_replace('<em>', '<mark>', $tag);
                                        $tag = str_replace('</em>', '</mark>', $tag);

                                        $highlights[] = $tag;
                                    }

                                    // encode html and clean it so we can usin it in the data- DOM
                                    $highlights = htmlentities(json_encode($highlights))
                                    ?>
                                    <button title="How I got this result?" data-highlights='<?= $highlights; ?>' @click="companyHighlights = JSON.parse($el.dataset.highlights); companyHighlightsModalShow = true;" class="absolute touch p-1  hover:cursor-help right-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2h-1.528A6 6 0 004 9.528V4z" />
                                            <path fill-rule="evenodd" d="M8 10a4 4 0 00-3.446 6.032l-1.261 1.26a1 1 0 101.414 1.415l1.261-1.261A4 4 0 108 10zm-2 4a2 2 0 114 0 2 2 0 01-4 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <a href="#" @click.prevent="if($el.dataset.company_uid != company.uid) fetchCompanyDetails($el.dataset.company_uid); companyDetailModalShow = true" data-company_uid="<?= $result['company_uid']; ?>" class="h-full">
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
                        <p>No results found for: <span class="font-semibold"><?= $_GET['problem']; ?></span></p>
                    </div>
                <?php }; ?>

            <?php }; ?>
        </div>

        <!-- company details modal -->
        <div x-cloak x-show="companyDetailModalShow" @company-detail.window="company = $event.detail" aria-labelledby="modal-title" role="dialog" aria-modal="true" class=" fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay, show/hide based on modal state.    -->
                <div x-show="companyDetailModalShow" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" aria-hidden="true" class="fixed inset-0 bg-gray-500 bg-opacity-25 transition-opacity"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel, show/hide based on modal state. -->
                <div x-show="companyDetailModalShow" @keydown.window.escape="companyDetailModalShow = false" @click.outside="companyDetailModalShow = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-10" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full sm:max-w-lg sm:p-6">
                    <div class="hidden sm:block absolute top-0 right-0 pt-4 pr-4">
                        <button type="button" @click="companyDetailModalShow = false" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500">
                            <span class="sr-only">Close</span>
                            <!-- Heroicon name: outline/x -->
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Company Details
                            </h3>
                            <div class="relative mt-5">
                                <div class="bg-white shadow overflow-hidden sm:rounded-lg">

                                    <div class="border-t border-gray-200">
                                        <dl>

                                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt class="text-sm font-medium text-gray-500">
                                                    Name
                                                </dt>
                                                <template x-if="company.website_url">
                                                    <a :href="company.website_url">
                                                        <dd x-text="company.name" class="mt-1 text-sm text-sky-500 hover:text-sky-400 sm:mt-0 sm:col-span-2"></dd>
                                                    </a>
                                                </template>

                                                <dd x-show="!company.website_url" x-text="company.name" class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"></dd>
                                            </div>


                                            <div x-data="{showLinksMenu: false}" class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt class="text-sm font-medium text-gray-500">
                                                    Symbol
                                                </dt>
                                                <a href="#" @click="showLinksMenu = true" :class="{ 'pointer-events-none': !company.media_links }">
                                                    <dd class="mt-1 inline-flex group text-sm  sm:mt-0 sm:col-span-2">
                                                        <div x-text="company.symbol"></div>
                                                        <!--
                                                        Heroicon name: solid/chevron-down
                                                        Item active: "text-gray-600", Item inactive: "text-gray-400"
                                                    -->
                                                        <svg :class="{ 'hidden': !company.media_links }" class="h-5 w-5 text-sky-500 group-hover:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                        </svg>
                                                    </dd>
                                                </a>

                                                <!-- Flyout menu, show/hide based on flyout menu state. -->
                                                <div x-show="showLinksMenu" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" @click.outside="showLinksMenu = false" class="absolute z-10 left-1/2 transform -translate-x-1/2 mt-7 px-2 w-screen max-w-xs sm:px-0">
                                                    <div class="rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden">
                                                        <div class="relative grid gap-6 bg-white px-5 py-6 sm:gap-8 sm:p-8">
                                                            <!-- media links for company -->
                                                            <template x-for="link in company.media_links">
                                                                <a :href="link.url" class="-m-3 p-3 block rounded-md even:bg-gray-50 hover:bg-gray-100 transition ease-in-out duration-150">
                                                                    <p x-text="link.site_name" class="text-base font-medium text-gray-900"></p>
                                                                </a>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt class="text-sm font-medium text-gray-500">
                                                    IPO Year
                                                </dt>
                                                <dd x-text="company.ipo_year" class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"></dd>
                                            </div>

                                            <div class="bg-white-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt class="text-sm font-medium text-gray-500">
                                                    Country
                                                </dt>
                                                <dd x-text="company.country" class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"></dd>
                                            </div>

                                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt class="text-sm font-medium text-gray-500">
                                                    Industry
                                                </dt>
                                                <dd x-text="company.industry" class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"></dd>
                                            </div>

                                            <div class="bg-white-5o px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt class="text-sm font-medium text-gray-500">
                                                    Sector
                                                </dt>
                                                <dd x-text="company.sector" class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"></dd>
                                            </div>

                                        </dl>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-4 sm:ml-10 sm:pl-4 sm:flex justify-end">
                        <button type="button" @click="companyDetailModalShow = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- company search hightlights modal -->
        <div x-cloak x-show="companyHighlightsModalShow" aria-labelledby="modal-title" role="dialog" aria-modal="true" class=" fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay, show/hide based on modal state.    -->
                <div x-show="companyHighlightsModalShow" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" aria-hidden="true" class="fixed inset-0 bg-gray-500 bg-opacity-25 transition-opacity"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel, show/hide based on modal state. -->
                <div x-show="companyHighlightsModalShow" @keydown.window.escape="companyHighlightsModalShow = false" @click.outside="companyHighlightsModalShow = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-10" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="hidden sm:block absolute top-0 right-0 pt-4 pr-4">
                        <button type="button" @click="companyHighlightsModalShow = false" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500">
                            <span class="sr-only">Close</span>
                            <!-- Heroicon name: outline/x -->
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Matched on:
                            </h3>

                            <div class="mt-5">
                                <div class="bg-white shadow overflow-hidden sm:rounded-lg">

                                    <div class="border-t border-gray-200">
                                        <dl>
                                            <template x-for="match in companyHighlights">
                                                <div class="even:bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                    <dd x-html="match" class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"></dd>
                                                </div>
                                            </template>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-4 sm:ml-10 sm:pl-4 sm:flex justify-end">
                        <button type="button" @click="companyHighlightsModalShow = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <!-- user feedback modal -->
        <div x-cloak x-show="feedbackModalShow" aria-labelledby=" modal-title" role="dialog" aria-modal="true" class="fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay, show/hide based on modal state. -->
                <div x-show="feedbackModalShow" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel, show/hide based on modal state.-->
                <div x-show="feedbackModalShow" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-10" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">

                    <!-- thanks for feedback message -->
                    <div id="feedback-success-message" @click.outside="feedbackModalShow = false" class="hidden bg-slate-100 w-full h-full absolute left-0 top-0 flex flex-col items-center justify-center z-10 gap-4">
                        <p class="font-bold ">Thanks for your feedback</p>

                        <button @click.prevent="feedbackModalShow = false; $el.parentElement.classList.add('hidden');" type=" button" class=" justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Close
                        </button>

                    </div>

                    <form id="user-feedback" class="" action="" @submit.prevent="sendUserFeedback" method="POST">
                        <div>
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                                <!-- heroicon/chat -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>

                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Feedback
                                </h3>
                                <small class="text-gray-600">Something you want to see added or fixed?</small>

                                <div class="mt-2">

                                    <div class="mt-4">
                                        <label for="email" class="sr-only">Email</label>
                                        <input type="email" name="email" id="email" required placeholder="your@email.com" class="shadow-sm focus:ring-zinc-500 focus:border-ring-zinc-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>

                                    <div class="mt-4">
                                        <label for="text" class="sr-only">Feedback</label>
                                        <textarea id="text" name="text" required class="shadow-sm focus:ring-zinc-500 focus:border-ring-zinc-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Your feedback..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-2 sm:text-sm">
                                Send
                            </button>
                            <button @click.prevent="feedbackModalShow = false" type=" button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-100 sm:mt-0 sm:col-start-1 sm:text-sm">
                                Cancel
                            </button>

                        </div>
                    </form>
                </div>
            </div>
        </div>

        <a href="" @click.prevent="feedbackModalShow = true" class="fixed left-5 -bottom-1 p-2 text-lg hover:scale-105 rounded-t-md bg-black text-white shadow-lg border-2 border-slate-500">
            Feedback

            <span class="absolute -right-1 -top-1 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 "></span>
            </span>
        </a>
    </main>
    <script>
        async function sendUserFeedback(e) {

            // send button is disabled to prevent multiple clicks
            e.submitter.disabled = true;
            document.querySelector('#feedback-success-message').classList.remove('hidden');

            let request = await fetch('/send-feedback', {
                method: 'POST',
                body: new FormData(e.target)
            });


            e.target.reset();

            // let's enable back the send button
            e.submitter.disabled = false;

        };

        async function fetchCompanyDetails(company_uid) {
            // console.log(`fetching company details for ${company_uid}`);

            document.querySelector('body').classList.add('cursor-progress');

            try {
                let request = await fetch(`/company-detail?uid=${company_uid}`);
                let response = await request.json();

                response.media_links = JSON.parse(response.media_links);

                let event = new CustomEvent("company-detail", {
                    detail: {
                        ...response
                    }
                });
                window.dispatchEvent(event);

            } catch (error) {
                alert('failed to retrieve company details. please try again');

            } finally {
                document.querySelector('body').classList.remove('cursor-progress');
            }


        }
    </script>
</body>

</html>