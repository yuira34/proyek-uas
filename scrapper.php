<?php
include_once('C:\xampp\htdocs\IIR\simplehtmldom_1_9_1\simple_html_dom.php');
echo '<link rel="stylesheet" href="style-sheet.css">';
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

extract($_POST);
$proxy = ($method == 'with_proxy') ? 'proxy3.ubaya.ac.id:8080' : null;
$key = ($keyword != "") ? str_replace(" ", "+", $keyword) : "";
$url = ($keyword != "") ? "https://scholar.google.com/scholar?q=$key&hl=en&as_sdt=0,5&as_rr=1" : "https://scholar.google.com$extension";
$html = getWebContent($url, $proxy);

if ($html != null) {
    $pagination = getPagination($html);

    $journals = crawlJournalContent($html, 10);
    $journals = crawlAuthorsJournal($journals, $proxy);
    $journals = crawlJournalAbstract($journals, $proxy);
    storeDisplayJournals($journals, $pagination);
}

// mengekstrak html menggunakan curl dengan header sehingga bisa tembus bot checker
function extractHtml($url, $proxy = null)
{
    $response = array();

    $response['code'] = '';

    $response['message'] = '';

    $response['status'] = false;

    $agent = $_SERVER['HTTP_USER_AGENT'];

    // Some websites require referrer

    $host = parse_url($url, PHP_URL_HOST);

    $scheme = parse_url($url, PHP_URL_SCHEME);

    $referrer = $scheme . '://' . $host;

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_HEADER, false);

    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_URL, $url);

    if ($proxy != null) { // cek apabila menggunakan proxy atau tidak
        curl_setopt($curl, CURLOPT_PROXY, $proxy);
    }

    curl_setopt($curl, CURLOPT_USERAGENT, $agent);

    curl_setopt($curl, CURLOPT_REFERER, $referrer);

    curl_setopt($curl, CURLOPT_COOKIESESSION, 0);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

    // allow to crawl https webpages

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

    // the download speed must be at least 1 byte per second

    curl_setopt($curl, CURLOPT_LOW_SPEED_LIMIT, 1);

    // if the download speed is below 1 byte per second for more than 30 seconds curl will give up

    curl_setopt($curl, CURLOPT_LOW_SPEED_TIME, 30);

    $content = curl_exec($curl);

    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    $response['code'] = $code;

    if ($content === false) {

        $response['status'] = false;

        $response['message'] = curl_error($curl);

    } else {

        $response['status'] = true;

        $response['message'] = $content;

    }

    curl_close($curl);

    return $response;
}

// cek dan konversi hasil ekstraksi html menjadi simple html dom object
function getWebContent($url, $proxy = null) 
{
    $response_html = extractHtml($url, $proxy);
    if ($response_html['code'] != '200') { // guard clause jika terjadi kode selain 200 (error)
        echo 'Code [' . $response_html['code'] . '] => Message: ' . $response_html['message'];
        return null;
    }

    $html = new simple_html_dom();
    $html->load($response_html['message']);

    return $html;
}

// mengambil data relevan dari simple html dom object menjadi array jurnal
function crawlJournalContent($html, $count) 
{
    $journals = array();
    for ($i = 0; $i < $count; $i++) {
        $journal = $html->find('div[class="gs_r gs_or gs_scl"]', $i); // tag obyek yaitu div yang menyimpan data jurnal
        $contents = [];

        //  Judul jurnal
        $contents['title'] = $journal->find('h3', 0)->find('a', 0)->plaintext;

        //  Penulis-penulis jurnal
        $journal_authors = $journal->find('div[class="gs_a"]', 0)->plaintext;
        $contents['authors'] = explode("-", $journal_authors)[0];

        // Link-link yang digunakan untuk mengambil abstrak jurnal
        $tag_object = $journal->find('div[class="gs_a"]', 0)->find('a', 0);
        $contents['author_link'] = ($tag_object != null) ? $tag_object->href : '';
        $contents['content_link'] = ($contents['author_link'] == '') ? $journal->find('h3', 0)->find('a', 0)->href : '';

        //  Jumlah sitasi jurnal
        $citation_count = $journal->find('div[class="gs_fl gs_flb"]', 0)->find('a', 2)->innertext;
        $contents['citation_count'] = preg_replace("/[^0-9]/", '', $citation_count);

        array_push($journals, $contents);
    }

    return $journals;
}

function crawlAuthorsJournal($journals, $proxy = null)
{
    foreach ($journals as $key => $journal) {
        if ($journal['author_link'] == '') {
            continue;
        } // guard clause untuk melewati jurnal yang tidak memiliki author_link

        $rows = findTitle($journal, 0, 100, $proxy); // cek jika judul belum ditemukan dan barisan sudah mencapai batas akhir

        if (count($rows) < 1) {
            continue;
        }
        $link = getTitleLink($journal, $rows, 0);
        $journals[$key]['content_link'] = $link;
    }

    return $journals;
}

function findTitle($journal, $step = 0, $size = 100, $proxy = null) 
{
    $path = $journal['author_link'];
    $title = strtolower($journal['title']);
    $size = ($size > 100) ? 100 : $size; // batas ukuran harus kurang dari sama dengan 100
    do {
        $start = $step * $size;
        $url = "https://scholar.google.com$path&cstart=$start&pagesize=$size";
        $html = getWebContent($url, $proxy);
        $step++;

        $table_body = $html->find('tbody[id="gsc_a_b"]', 0); // badan tabel yang berisi baris baris jurnal penulis

        $table_text = strtolower($table_body->plaintext); // ekstrak semua teks dari badan tabel
        $title_found = (str_contains($table_text, $title)); // cek apakah judul ada di badan tabel

        $rows = $table_body->find("tr[class='gsc_a_tr']"); // array yang memiliki jurnal yang dicari

        $row_count = count($rows);
        $rows_limit = ($row_count < $size); // cek jumlah baris jika kurang dari ukuran yang diharapkan
    } while (!$title_found && !$rows_limit); // Pencarian jurnal selesai jika telah ditemukan atau sampai di halaman terakhir

    return $rows; // mengembalikan array berisi jurnal dengan judul yang dicari
}


function getTitleLink($journal, $rows, $index = 0) 
{
    $row_count = count($rows);
    $title = strtolower($journal['title']);
    do { // cari url/link untuk judul jurnal dari halaman penulis
        $row_title = strtolower($rows[$index]->find('a', 0)->innertext);
        $row_link = $rows[$index]->find('a', 0)->href;
        $link_found = (str_contains($row_title, $title) || str_contains($title, $row_title) || $title == $row_title);
        $index++;
    } while (!$link_found && ($index < $row_count));

    // konversi url path menjadi url web yang dapat dibaca oleh algoritma
    $link = ($link_found) ? html_entity_decode("https://scholar.google.com$row_link") : $journal['content_link'];

    return $link;
}

function crawlJournalAbstract($journals, $proxy = null)
{
    foreach ($journals as $key => $journal) {
        $text_array = getWebpageTexts($journal, $proxy);
        $journals[$key]['abstract'] = getAbstractText($text_array, 0);
    }
    return $journals;
}

function getWebpageTexts($journal, $proxy = null)
{
    $html = getWebContent($journal['content_link'], $proxy);
    $elements = $html->find("*");
    $child_elements = [];
    foreach ($elements as $element) {
        if ($element->children != null) {
            continue;
        }

        $child_elements[] = $element->plaintext;
    }

    return array_values(array_filter($child_elements));
}

function getAbstractText(array $text_array, $index = 0)
{
    $anchor = ["abstract", "description"];
    do {
        $text = strtolower($text_array[$index]);
        $index++;
    } while (!in_array($text, $anchor) && str_word_count($text_array[$index]) < 80);
    return $text_array[$index];
}

function storeDisplayJournals($journals, $pagination)
{
    if ($journals == false) {
        return false;
    }

    $connection = mysqli_connect("localhost", "root", "", "iir_uas");
    foreach ($journals as $details) {
        $title = $details['title'];
        $authors = $details['authors'];
        $abstract = $details['abstract'];
        $citation_count = $details['citation_count'];
        echo "<div>Title : $title<br>";
        echo "Authors: $authors <br>";
        echo "Abstract: <br> <div style='text-align:justify'> $abstract </div> <br>-----------------------------------------<br>";
        echo "Number of Citation: $citation_count <br><br>=================================================<br></div>";
        $sql = "INSERT INTO journals(title, citation_count, authors, abstract) VALUES ('$title', $citation_count,'$authors', '$abstract')";
        mysqli_query($connection, $sql);
    }
    echo "<div>$pagination</div>";
    mysqli_close($connection);
}

function getPagination($html)
{
    return $html->find('#gs_nml', 0);
}
?>