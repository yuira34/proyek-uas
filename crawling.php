<!DOCTYPE html>
<html>
<link rel="stylesheet" href="style-sheet.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<head>
    <title>Crawling</title>
</head>

<body>
    <div class="top-section">
        <a href="home.php">Home</a>
        <div>&nbsp;|&nbsp;</div><a href="">Crawling</a>
    </div>
    <div class="title">
        <h1>Data Crawling From Google Scholar</h1>
    </div>
    <div class="search-area">
        <form method="POST" action="">
            <div>keyword:&nbsp;</div>
            <div class="input-icons">
                <i class="fa fa-search icon"></i>
                <input class="input-field" placeholder="Search" type="text" name="keyword">
            </div>
            <div><input class="crawl-button" type="submit" name="crawl" value="Crawls"></div>
        </form>
    </div>
    <div class="result-display">
        <?php
            include('test.php');
        ?>
    </div>
</body>

</html>