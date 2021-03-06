#!/usr/bin/php -q
<?php
# 上櫃股票
require_once('./auto_load.php');
if($argc != 2)
	die('Syntax : '.$argv[0].' yyyy-mm-dd'."\n");
try {
	$current_date = new DateTime($argv[1]);
}catch(Exception $e) {
	die('Syntax : '.$argv[0].' yyyy-mm-dd'."\n");
}
$Year = $current_date->format('Y');
$Year2 = $Year-1911;
$Month = $current_date->format('m');
$Day = $current_date->format('d');
error_log('['.date('Y-m-d H:i:s').'] '.__FILE__ . ' Start'."\n",3,'./log/stock.log');
error_log('['.date('Y-m-d H:i:s').'] '.__FILE__ . ' Start'."\n");
$dbh = new PDO($DB['DSN'],$DB['DB_USER'], $DB['DB_PWD'],
		array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
			PDO::ATTR_PERSISTENT => false));
# 錯誤的話, 就不做了
$dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_WARNING);
$days = $Year.$Month.$Day;
$p1 = $dbh->prepare("select * from `history_data` where days=:days and stock_type=2 limit 1");
$p2 = $dbh->prepare("insert into `history_data` (`days`,`stock_id`,`stock_name`,`deal_amount`,`start_price`,`highest_price`,
		`lowest_price`,`end_price`,`stock_type`,`created_at`) values (:days,:stock_id,:stock_name,:deal_amount,
		:start_price,:highest_price,:lowest_price,:end_price,2,now())");

$p1->execute(array('days'=>$days));
if($p1->rowCount() !== 0)
{
	error_log('['.date('Y-m-d H:i:s').'] '.__FILE__ .' '.$days.' has exist data'."\n",3,'./log/stock.log');
	error_log('['.date('Y-m-d H:i:s').'] '.__FILE__ .' '.$days.' has exist data'."\n");
	die();
}
//http://www.gretai.org.tw/ch/stock/aftertrading/otc_quotes_no1430/stk_wn1430_print.php?d=103/04/30&se=EW&s=0,asc,0
$url = 'http://www.gretai.org.tw/ch/stock/aftertrading/otc_quotes_no1430/stk_wn1430_print.php?d='.
		$Year2.'/'.$Month.'/'.$Day.'&se=EW&s=0,asc,0';
$data = file_get_contents($url);
//file_put_contents('2.html',$data);
//exit;

$DataArray = array();
//$data = file_get_contents('2.html');
$html = str_get_html($data);
if(!is_object($html) or !isset($html->nodes))
{
	error_log('['.date('Y-m-d H:i:s').'] '.__FILE__ .' '.$days.' html is not object'."\n",3,'./log/stock.log');
	error_log('['.date('Y-m-d H:i:s').'] '.__FILE__ .' '.$days.' html is not object'."\n");
	die();
}
foreach($html->find('table tr') as $tr)
{
	$itemArray = array();
	if(is_object($tr->children(8)))
	{
		$itemArray = array();
		$itemArray['days'] = $days;
		$itemArray['stock_id'] = $tr->children(0)->plaintext;			//char
		$itemArray['stock_name'] = $tr->children(1)->plaintext;			//char
		$amount = str_replace(",","",$tr->children(7)->plaintext);
		$itemArray['deal_amount'] = intval($amount);
		$amount = str_replace(",","",$tr->children(4)->plaintext);
		$itemArray['start_price'] = floatval($amount);
		$amount = str_replace(",","",$tr->children(5)->plaintext);
		$itemArray['highest_price'] = floatval($amount);
		$amount = str_replace(",","",$tr->children(6)->plaintext);
		$itemArray['lowest_price'] = floatval($amount);
		$amount = str_replace(",","",$tr->children(2)->plaintext);
		$itemArray['end_price'] = floatval($amount);
		if(!preg_match('/^[0-9]{1}/',$itemArray['stock_id']))	// Ingore
		{
			//echo $itemArray['stock_id']."\n";
			unset($itemArray);
			continue;
		}
		try {
			$p2->execute($itemArray);
		} catch (PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__FILE__ .' Error : ('.$e->getLine().') '.$e->getMessage()."\n",3,'./log/stock.log');
			error_log('['.date('Y-m-d H:i:s').'] '.__FILE__ .' Error : ('.$e->getLine().') '.$e->getMessage()."\n");
			unset($itemArray);
			continue;	
		}
		unset($itemArray);
	}
}
error_log('['.date('Y-m-d H:i:s').'] '.__FILE__ . ' Finish'."\n",3,'./log/stock.log');
error_log('['.date('Y-m-d H:i:s').'] '.__FILE__ . ' Finish'."\n");
