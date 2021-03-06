<?php
if (isset($_GET['q'])
&& !empty($_GET['q'])
&& isset($_GET['check'])
&& $_GET['check'] == getKbAmz()->getSecret()) {
    set_time_limit(90);
    require_once KbAmazonStorePluginPath . 'lib/phpQuery-onefile.php';
    
    $importer = new KbAmazonImporter;
    
    $ids = array();
    $response = $importer->getUrlResponse($_GET['q']);
    phpQuery::resetDocuments();
    $doc = phpQuery::newDocumentHTML($response, 'utf-8');
    $as = $doc->find('a');
    foreach($as as $a) {
        $href = pq($a)->attr('href');
        $id = $importer->getBetween($href,'dp/','/');
        if (!$id) {
            $id = $importer->getBetween($href,'product/','/');
        }
        $src = null;
        if ($id) {
            $img = pq($a)->find('img');
            if ($img) {
                foreach ($img as $im) {
                   $src = pq($im)->attr('src');
                   break;
                }
            }
        }

        if (!empty($id) && !in_array($id, $ids) && $src) {
            $ids[] = array(
                'asin' => $id,
                'src' => $src
            );
        }
    }
    echo serialize($ids);
    exit;
}