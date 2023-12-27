<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="style-sheet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- jQuery script for handling pagination with Ajax -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.page-link').click(function (e) {
                e.preventDefault();
                var page = $(this).data('page');
                var keyword = $(this).data('keyword');
                var method = $(this).data('method');
                // Perform Ajax request to load data for the selected page
                $.ajax({
                    url: 'similarity.php', // Create a separate PHP file to handle Ajax requests
                    type: 'POST',
                    data: { keyword: keyword, method: method, page: page },
                    success: function (response) {
                        // Update the content area with the new data
                        $('#content').html(response);
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });

            $(".search-button").click(function (e) {
                var keyword = $('#keyword').val();
                var method = $('input[name="method"]:checked').val();
                e.preventDefault();
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
    <title>Home</title>
</head>
<body>
    <div class="top-section">
        <a href="">Home</a>
        <div>&nbsp;|&nbsp;</div>
        <a href="crawling.php">Crawling</a>
    </div>
    <div class="title">
        <h1>Welcome to Scientific Journals Search Engine</h1>
    </div>
    <div class="search-area">
        <div>keyword:&nbsp;</div>
        <div class="input-icons">
            <i class="fa fa-search icon"></i>
            <input class="input-field" placeholder="Search" id="keyword" name="keyword" type="text">
        </div>
        <div><input type="submit" class="search-button" value="search"></div>
    </div>
    <div class="search-area">
        <input type="radio" id="euclidean" name="method" value="euclidean" checked><label for="euclidean">Euclidean
            Distance</label>
        <input type="radio" id="cosine" name="method" value="cosine"><label for="cosine">Cosine Coefficient</label>
    </div>
    <div class="result-display" id="content">
    </div>
</body>

</html>