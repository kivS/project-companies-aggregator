<?php
require_once(__DIR__ . '/../.env.php');
require_once __DIR__ . '/vendor/autoload.php';

use MeiliSearch\Client;



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

    <style>
        h1 {
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: row;
            align-content: center;
            justify-content: center;
        }

        input {
            width: 300px;
            height: 50px;
            border-radius: 25px;
            text-align: center;
        }

        li{
            border: 1px solid red;
        }
    </style>
</head>

<body>
    <h1>Discover what companies are working on what problems</h1>

    <form action="" method="get">
        <input type="search" list="problems-completion" name="problem" autocomplete="off" placeholder="water, energy, climate change, etc...">
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
                <p>Found <?= $search_results['nbHits']; ?></p>
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
        <?php }else{ ?>
            <div>
                <p>No results found</p>
            </div>
        <?php }; ?>

    <?php }; ?>
</body>

</html>