<!DOCTYPE html>
<html>
<link rel="stylesheet" href="style-sheet.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    $(document).ready(function () {
        $('.gs_nma').click(function (e) {
            e.preventDefault();
            var extension = $(this).attr('href');
            var method = $('input[name="method"]:checked').val();
            // Perform Ajax request to load data for the selected page
            $.ajax({
                url: 'scrapper.php', // Create a separate PHP file to handle Ajax requests
                type: 'POST',
                data: { keyword: "", method: method, extension: extension },
                success: function (response) {
                    // Update the content area with the new data
                    $('#content').html(response);
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });

        $('.crawl-button').click(function (e) {
            e.preventDefault();
            var keyword = $('#keyword').val();
            var method = $('input[name="method"]:checked').val();
            // Perform Ajax request to load data for the selected page
            $.ajax({
                url: 'scrapper.php', // Create a separate PHP file to handle Ajax requests
                type: 'POST',
                data: { keyword: keyword, method: method, extension: "" },
                success: function (response) {
                    // Update the content area with the new data
                    $('#content').html(response);
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });

        $('.query-expansion').click(function (e) {
            e.preventDefault();
            var keyword = $(this).data('keyword');
            var method = $(this).data('method');
            // Perform Ajax request to load data for the selected page
            $.ajax({
                url: 'similarity.php', // Create a separate PHP file to handle Ajax requests
                type: 'POST',
                data: { keyword: keyword, method: method, page: 1 },
                success: function (response) {
                    // Update the content area with the new data
                    $('#content').html(response);
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });
    });
</script>
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
    <form method="POST" action="">
        <div class="search-area">
            <div>keyword:&nbsp;</div>
            <div class="input-icons">
                <i class="fa fa-search icon"></i>
                <input class="input-field" placeholder="Search" id="keyword" name="keyword" type="text">
            </div>
            <div><button class="crawl-button" name="crawls">Search</button></div>
        </div>
        <div class="search-area">
            <input type="radio" id="with_proxy" name="method" value="with_proxy" ><label for="with_proxy">With Proxy</label>
            <input type="radio" id="without_proxy" name="method" value="without_proxy" checked><label for="without_proxy">Without Proxy</label>
        </div>
    </form>
    <div class="result-display" id="content">
    </div>
</body>

</html>