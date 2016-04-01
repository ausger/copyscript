<?php 

global $session_id; global $soap; require_once('includes/header.php'); if($session_id){ ?>

<form action="" method="post">
  <p>
    <select name="store" id="store">
      <option value="1" <?php if($_POST['store']=='1'){ echo 'selected="selected"'; }; ?>>Main Store to Store 1</option>
      <option value="2" <?php if($_POST['store']=='2'){ echo 'selected="selected"'; }; ?>>Main Store to Store 2</option>
    </select>
  </p>
  <p>
    <label for="SKU">SKU</label>
    <input name="SKU" type="text" id="SKU" value="<?php echo $_POST['SKU'] ?>" />
    
    <label for="CATEGORIES">CATEGORIES</label>
    <input name="CATEGORIES" type="text" id="CATEGORIES" value="<?php echo $_POST['CATEGORIES'] ?>" size="25" maxlength="100" />
  </p>
  <p>
    <input type="submit" name="button" id="button" value="Copy Product" />
  </p>
</form>
<?php

if($_POST['SKU']!='' & $_POST['store']!='' & $_POST['CATEGORIES']!=''){

	$sku_field = $_POST['SKU'];
	//echo $sku_field;
	$description_field = '';
	$description_image = '';
	$name_zh = '';
	$short_description_field = '';
	$meta_title_field = '';
	$meta_key_field = '';
	$meta_description_field = '';

	if(strpos($_POST['SKU'], '|') !== false) {
		//split it
		$prod_info = explode('|', $_POST['SKU']);
		//d e s c r i p t i o n|i m g|n a m e - d e|n a m e - z h|s k u|m e t a - t i t l e|m e t a - k e y w o r d s|m e t a - d e s c r i p t i o n|s h o r t - d e s c r i p t i o n
		$sku_field = preg_replace('/\s+/', '',  $prod_info[4]);
		echo "sku is " . $sku_field;
		$description_field = preg_replace('/\s+/', '',  $prod_info[0]);
		echo 'description: ' . $description_field;
		$description_image = preg_replace('/\s+/', '',  $prod_info[1]);
		$name_zh = preg_replace('/\s+/', '',  $prod_info[3]);
		echo 'name is ' . $name_zh;
		$short_description_field = preg_replace('/\s+/', '',  $prod_info[8]);
		$meta_title_field = preg_replace('/\s+/', '',  $prod_info[5]);
		$meta_key_field = preg_replace('/\s+/', '',  $prod_info[6]);
		$meta_description_field = preg_replace('/\s+/', '',  $prod_info[7]);

	}

	$categories = explode(',' , $_POST['CATEGORIES']);
	//var_dump($categories);

	if($_POST['store']=='1'){
		$mage_2_url = 'http://ausger.dev:8888/de/api/soap/?wsdl';
		$mage_2_user = 'ausgerdev';
		$mage_2_api_key = '12345678';
	}else if($_POST['store']=='2'){
		$mage_2_url = 'http://ausger.dev:8888/zh/api/soap/?wsdl';
		$mage_2_user = 'ausgerdev';
		$mage_2_api_key = '12345678';
	}
	$soap_2 = new SoapClient( $mage_2_url );

	$product = $soap->call($session_id, 'catalog_product.info', $sku_field);
	var_dump($product);
	if($product['type']=="simple"){
		//copy product
		$session_id_2 = $soap_2->login( $mage_2_user, $mage_2_api_key );

		if(strpos($product['sku'], '-de') !== false){
			$new_sku = str_replace('-de','-zh', $product['sku']);
		} else {
			$new_sku = "zh-" . $product['sku'];
		}

		echo $new_sku;
		$product['sku'] = $new_sku;
		//3 => Chinese Category
		//	7 => 母婴
		//			88 => 保温杯
		//			89 => 水瓶
		//			90 => 保温保暖
		//$product['categories'] = array(3,7,88);
		$product['categories'] = $categories;
		$product['visibility']= '4';
		$product['tax_class_id']= '0';
		if(!empty($description_field)) {
			$product['description']= $description_field . $description_image;
		}
		if(!empty($short_description_field)) {
			$product['short_description']= $short_description_field;
		}
		if(!empty($name_zh)) {
			$product['name'] = $name_zh;
		}
		if(!empty($meta_title_field)) {
			$product['meta_title'] = $meta_title_field;
		}
		if(!empty($meta_key_field)) {
			$product['meta_keyword'] = $meta_key_field;
		}
		if(!empty($meta_description_field)) {
			$product['meta_description'] = $meta_description_field;
		}

		$result = $soap_2->call($session_id_2, 'catalog_product.create', array($product['type'], $product['set'], $new_sku, $product));
		var_dump($result);

		//copy options
		$product_options = $soap->call($session_id, 'product_custom_option.list', $sku_field);
		$product_options_data = array();

		foreach($product_options as $product_options_data){
			$product_options_get_data = $soap->call($session_id, 'product_custom_option.info', $product_options_data['option_id']);
			//FIX
			for($i=0;$i < count($product_options_get_data['additional_fields']) ;$i++){
				unset($product_options_get_data['additional_fields'][$i]['value_id']);
			}
			$result2 = $soap_2->call($session_id_2,"product_custom_option.add", array($result, $product_options_get_data));
		}

		//copy media
		$product_images = $soap->call($session_id, 'catalog_product_attribute_media.list', $sku_field);
		var_dump($product_images);
		for($i=0;$i < count($product_images) ;$i++){
			unset($product_images[$i]['file']);
			$curl = curl_init($product_images[$i]['url']);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
			$ret_val = curl_exec($curl);
			if(!curl_errno($curl)){
				$file = array(
					'content' => chunk_split(base64_encode($ret_val)),
					'mime' => curl_getinfo($curl , CURLINFO_CONTENT_TYPE),
				);
				$product_images[$i]['file']=$file;
				$result2 = $soap_2->call($session_id_2,"catalog_product_attribute_media.create", array($result, $product_images[$i]));
			}
			curl_close($curl);
		}

		$stockItemData = array(
			'qty' => '50',
			'is_in_stock' => 1,
			'manage_stock' => 1,
			'use_config_manage_stock' => 0,
			'min_qty' => 2,
			'use_config_min_qty' => 0,
			'min_sale_qty' => 1,
			'use_config_min_sale_qty' => 0,
			'max_sale_qty' => 10,
			'use_config_max_sale_qty' => 0,
			'is_qty_decimal' => 0,
			'backorders' => 1,
			'use_config_backorders' => 0,
			'notify_stock_qty' => 10,
			'use_config_notify_stock_qty' => 0
		);

		$result = $soap_2->call(
			$session_id_2,
			'product_stock.update',
			array(
				$new_sku,
				$stockItemData
			)
		);

		echo "<strong>FINISHED: Product Copied</strong>";

		$soap_2->endSession($session_id_2);
	}else{
		echo "Only Simple Products are supported at this stage";
	};
}


?><br />
<a href="index.php">Back</a>
<?php } require_once('includes/footer.php'); ?>
