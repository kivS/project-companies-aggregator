<?php

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
    </style>
</head>

<body>
    <h1>Discover what companies are working on what problems</h1>
    <form action="" method="get">
        <input type="search" list="problems-completion" name="problem" autocomplete="off" placeholder="water, energy, climate change, etc...">

        <datalist id="problems-completion">
            <option value="Water">
            <option value="Energy">
            <option value="Climate Change">
            <option value="Health">
            <option value="Housing">
        </datalist>
    </form>
</body>

</html>