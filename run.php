<?php
  include_once("simple_html_dom.php"); //MIT License

  $date_url = date('Y/n/j');
  $webhook = "https://hooks.slack.com/services/..."; //Slack incoming webhook url
  $channel = "#general"; // Slack channel
  $icon_url = "";
  $link_wol = 'http://wol.jw.org/de/wol/dt/r10/lp-x/' . $date_url;
  $api_wol = 'http://wol.jw.org/wol/dt/r10/lp-x/' . $date_url;

  $obj = json_decode(file_get_contents($api_wol), true, 512, JSON_UNESCAPED_UNICODE);

  $dom = str_get_html(mb_convert_encoding( $obj['items'][0]['content'], "UTF-8"));

  $title = $dom->find('h2',0)->plaintext; //Get title
  $pretext = convertLinks(mb_convert_encoding(removeTags($dom->find('.themeScrp',0)), 'UTF-8', 'HTML-ENTITIES')); //get pretext
  $bodytext = convertLinks(mb_convert_encoding(removeTags($dom->find('.sb',0)), 'UTF-8', 'HTML-ENTITIES')); //get Bodytext


  //Slack send message.
  $data = array('payload' => '{  "icon_url": "' . $icon_url . '", "channel": "' . $channel . '", "username": "Tagestext vom ' . htmlspecialchars($title) . '", "attachments": [{ "title": "' . $pretext . '","title_link": "' . $link_wol . '", "text": "' . $bodytext . '" }]}');
  $options = array(
      'http' => array(
          'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
          'method'  => 'POST',
          'content' => http_build_query($data),
      ),
  );
  $context  = stream_context_create($options);
  $result = file_get_contents($webhook, false, $context);
  echo $result;


  function convertLinks($text){
    $text = str_replace('<a href="', '<http://wol.jw.org/de', $text);
    $text = str_replace('">', '|', $text);
    $text = str_replace('</a>', '>', $text);
    return $text;
  }

  function removeTags($data_str){
    //     http://stackoverflow.com/a/7582804
    $allowable_tags = '<a>';
    $allowable_atts = array('href');
    $strip_arr = array();
    $data_sxml = simplexml_load_string('<root>'. $data_str .'</root>', 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOXMLDECL);

    if ($data_sxml ) {
        foreach ($data_sxml->xpath('descendant::*[@*]') as $tag) {
            foreach ($tag->attributes() as $name=>$value) {
                if (!in_array($name, $allowable_atts)) {
                    $tag->attributes()->$name = '';
                    $strip_arr[$name] = '/ '. $name .'=""/';
                }
            }
        }
    }
    return $data_str = strip_tags(preg_replace($strip_arr,array(''),$data_sxml->asXML()), $allowable_tags);
  }
?>
