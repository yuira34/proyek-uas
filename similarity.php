<?php
use markfullmer\porter2\Porter2;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Math\Distance\Euclidean;
use Phpml\Tokenization\WhitespaceTokenizer;
use StopWords\StopWords;

require_once 'C:\xampp\htdocs\IIR\vendor\autoload.php';

extract($_POST); // ekstrak kata kunci (keyword), metode perhitungan similaritas (method), halaman (page)
$euclidean = new Euclidean();

$connection = mysqli_connect("localhost", "root", "", "iir_uas");
$statement = "SELECT * FROM journals";
$result = mysqli_query($connection, $statement);

$sample_data = array();
$journals = array();
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $stopword_removed = preprocess_data($row['title'], $row['abstract']);
        array_push($sample_data, $stopword_removed);
        $journals[] = array(
            'title' => $row['title'],
            'authors' => $row['authors'],
            'abstract' => $row['abstract'],
            'citation_count' => $row['citation_count']
        );
    }
    array_push($sample_data, str_replace("%20", " ", $keyword));
} else
    echo "Data is still empty. Please crawl first";

[$sample_data, $vocabulary] = term_freq_transform($sample_data);

$tf_idf = new TfIdfTransformer($sample_data);
$tf_idf->transform($sample_data);

$index = 0;
$query = array_pop($sample_data);

foreach ($sample_data as $key => $sample) {
    $similarity_score = match ($method) {
        'euclidean' => $euclidean->distance($sample, $query),
        'cosine' => cosine_calculator($sample, $query)
    };
    $journals[$key]['score'] = round($similarity_score, 4);
}

// Urutkan array jurnal berdasarkan hasil perhitungan skor jarak atau koefisien
usort($journals, fn($i, $j) => ($method != 'euclidean') ? $i['score'] < $j['score'] : $i['score'] > $j['score']);

if (count($sample_data) > 0) {
    $top_3 = array_slice($journals, 0, 3);
    $query_expansions = get_query_expansion($keyword, $top_3, 5);
    echo "<div style='float: left; width: 20%; margin-right: 20px;'>";
    echo "<b>Related Search</b><br>";
    foreach ($query_expansions as $key_expanded) {
        $query_key = strip_tags($key_expanded);
        echo "<a class='query-expansion' data-key='$query_key' data-method='$method'>$key_expanded</a>";
    }
    echo "</div>";
}

// Calculate the offset for the array slice
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get a slice of the array based on the current page
$diplay_page = array_slice($journals, $offset, $per_page);

// Total number of pages
$pages_count = ceil(count($journals) / $per_page);

echo "<div style='float: left; width: 70%;'>";
echo "<b>The Search Result</b><br>";
foreach ($diplay_page as $index => $contents) {
    $line = '';
    foreach ($contents as $key => $value) {
        $line .= match ($key) {
            'title' => "Title : $value<br>",
            'authors' => "Authors: $value <br>",
            'abstract' => "Abstract: <br><div style='text-align:justify'>$value</div><br>-----------------------------------------<br>",
            'citation_count' => "Number of Citation: $value <br>",
            'score' => "Similarity score: $value <br><br>========================================<br>"
        };
    }
    echo "<div>$line<div>";
}
// Display pagination links using jQuery
echo "";
echo '<div class="pagination">';
for ($i = 1; $i <= $pages_count; $i++) {
    echo ($i != $page) ? "<a href='#' class='page' data-page='$i' data-keyword='$keyword' data-method='$method'>$i</a>" : "<b>$i</b>";
}
echo '</div>';
echo "</div>";
mysqli_close($connection);

function preprocess_data($title, $abstract)
{
    $stopword_remover = new StopWords('en');

    $combined = strtolower("$title, $abstract");
    $stemmer = Porter2::stem($combined);
    return $stopword_remover->clean($stemmer);
}

function cosine_calculator($data, $query)
{
    $numerator = .0;
    $denom_wkq = .0;
    $denom_wkj = .0;

    for ($j = 0; $j < count($data); $j++) {
        $numerator += $query[$j] * $data[$j];
        $denom_wkq += pow($query[$j], 2);
        $denom_wkj += pow($data[$j], 2);
    }

    if (pow($denom_wkq * $denom_wkj, .5) != 0) {
        $result = $numerator / pow($denom_wkq * $denom_wkj, .5);
    } else
        $result = 0;

    return $result;
}

function get_query_expansion($query, array $terms, $expansion_count)
{
    $sample_data = array_map(fn($detail) => preprocess_data($detail['title'], $detail['abstract']), $terms);

    [$sample_data, $vocabulary_array] = term_freq_transform($sample_data);

    $data_new = [];

    foreach ($vocabulary_array as $index_vocab => $word) {
        $data_new[$word] = array_sum(array_column($sample_data, $index_vocab));
    }

    arsort($data_new);
    $expansion_term = array_keys($data_new);
    $query_array = explode(" ", $query);

    $expansions_array = array_diff($expansion_term, $query_array);
    $query_expansion = array_map(fn($value) => "$query <b>$value</b>", $expansions_array);

    return (count($query_expansion) > 0) ? array_slice($query_expansion, 0, $expansion_count) : null;
}

function term_freq_transform(array $data)
{
    $expansion_tf = new TokenCountVectorizer(new WhitespaceTokenizer());
    $expansion_tf->fit($data);
    $expansion_tf->transform($data);

    $vocabulary_array = $expansion_tf->getVocabulary();

    return [$data, $vocabulary_array];
}

function trim_query($query)
{
    $count = explode(" ", $query);
    if (count($count) >= 3) {
        $query = $count[1] . " " . $count[0];
    }
    return $query;
}