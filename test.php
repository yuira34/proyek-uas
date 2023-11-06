<?php
include_once('C:\xampp\htdocs\IIR\uas-project\simplehtmldom_1_9_1\simple_html_dom.php');
echo '<link rel="stylesheet" href="style-sheet.css">';
error_reporting(E_ERROR | E_PARSE);
set_time_limit(300);

$html = file_get_html("https://scholar.google.com/scholar?hl=en&as_sdt=0%2C5&q=computer+vision&oq=");
$datas = $html->find('div[class="gs_r gs_or gs_scl"]');

$journals = array();

foreach ($datas as $journal) {
    //  Judul
    $journal_title = $journal->find('h3', 0)->find('a', 0)->plaintext;

    //  Penulis
    $journal_authors = $journal->find('div[class="gs_a"]', 0)->plaintext;
    $journal_authors = explode("-", $journal_authors)[0];

    // // Abstrak
    $journal_link = $journal->find('h3', 0)->find('a', 0)->href;

    //  Jumlah sitasi
    $citation_count = $journal->find('div[class="gs_fl gs_flb"]', 0)->find('a', 2)->innertext;
    $citation_count = preg_replace("/[^0-9]/", '', $citation_count);

    $details = array(
        'Authors' => $journal_authors,
        'link' => $journal_link,
        'Abstract' => "Abstract cannot be located",
        'Number of Citation' => $citation_count
    );

    $journals[$journal_title] = $details;
}

foreach ($journals as $key => $journal) {
    $link = $journal['link'];

    $opts = array(
        'http' => array(
            'method' => "GET",
            'header' => "Accept-language: en\r\n" .
                "User-Agent:    Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36\r\n" .
                "Cookie: foo=bar\r\n"
        )
    );
    $context = stream_context_create($opts);

    $content = file_get_html($link, false, $context);

    if (!$content)
        continue;

    $index = 0;
    $paragraphs = $content->find('p');

    do {
        $paragraph = $paragraphs[$index]->plaintext;
        $word_count = str_word_count($paragraph);

        // 2 Kondisi yang harus terpenuhi untuk do while looping
        $is_abstract = ($word_count > 50 && $word_count <= 300);
        $paragraph_counter = ($index < count($paragraphs));

        $index++;
    } while (!$is_abstract && $paragraph_counter);

    // Jika abstrak ditemukan maka simpan ke dalam journals
    if ($is_abstract)
        $journals[$key]['Abstract'] = strip_tags($paragraph);
}

$connection = mysqli_connect("localhost", "root", "mysql", "news");
foreach ($journals as $title => $details) {
    echo "Title : $title<br>";

    $authors = $details['Authors'];
    $abstract = $details['Abstract'];
    $citation_count = $details['Number of Citation'];
    foreach ($details as $key => $value) {
        if ($key != "link" && $key != "Abstract") echo "$key : <br>$value <br>";
        elseif ($key == "Abstract") echo "Abstract :<br><p style='text-align: justify; text-justify: inter-word;'>$value</p>";

        if ($key == "Abstract") echo "-----------------------------------------<br>";
        if ($key == "Number of Citation") echo "<br><hr class='solid'>";
    }
    
    $sql = "INSERT INTO journals VALUES ('$title','$citation_count','$authors', $abstract)";
    mysqli_query($connection, $sql);
}

if (isset($_POST['crawl'])) {
    $key = str_replace(" ", "+", $_POST["keyword"]);
    $start = 0;
    $html = file_get_html("https://scholar.google.com/scholar?start=$start&q=$key&hl=en&as_sdt=0,5");

    $journals = array();
    foreach ($html->find('div[class="gs_r gs_or gs_scl"]') as $journal) {

        //  Judul
        $journal_title = $journal->find('h3', 0)->find('a', 0)->plaintext;

        //  Penulis
        $journal_authors = $journal->find('div[class="gs_a"]', 0)->plaintext;
        $journal_authors = explode("-", $journal_authors)[0];

        // // Abstrak
        $journal_link = $journal->find('h3', 0)->find('a', 0)->href;

        //  Jumlah sitasi
        $citation_count = $journal->find('div[class="gs_fl gs_flb"]', 0)->find('a', 2)->innertext;
        $citation_count = preg_replace("/[^0-9]/", '', $citation_count);

        $contents = array(
            'authors' => $journal_authors,
            'link' => $journal_link,
            'citation_count' => $citation_count
        );

        $journals[$journal_title] = $contents;
    }

    foreach ($journals as $key => $journal) {
        $content = file_get_html($journal['link']);
        $index = 0;

        do {
            $paragraph = $content->find('p', $index);
            $word_count = str_word_count($paragraph);
            $is_abstract = ($word_count > 80 && $word_count <= 300);
            $index++;
        } while (!$is_abstract);

        $journals[$key]['Abstract'] = strip_tags($paragraph->plaintext);
    }

    echo "<pre>";
    print_r($journals);
    echo "</echo>";
}
?>