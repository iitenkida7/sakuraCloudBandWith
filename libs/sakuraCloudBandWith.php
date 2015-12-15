<?php


class  sakuraCloudBandWith {
	private $token         = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
	private $secretToken   = "YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY";
	private $baseURL       = "secure.sakura.ad.jp/cloud/zone/is1b/api/cloud/1.1/internet"; // 石狩第２ 
	private $maxLimit      = 50; //xMbpsを超えたら　、500Mbps 帯域に変更
	private $minLimit      = 30; //xMbpsを下回ったら、100Mbps 帯域に変更

	private $routerID      = "";
	private $BandWidthMbps = "";

	private function getBaseURL(){
		return "https://" . $this->token . ":" . $this->secretToken . "@" . $this->baseURL;		
	}


	public function run(){
		$Traffic = $this->checkTraffic();
			echo '['.date("Y:m:d H:i:s").']BandWidthMbps:'.$this->BandWidthMbps ."Mbps\tTraffic:" .$Traffic."Mbps\n";	
		if($Traffic > $this->maxLimit  AND $Traffic != 0){
			if($this->BandWidthMbps == 100 ){
				$this->changeBandWidth($this->routerID,500);
				echo '['.date("Y:m:d H:i:s").']changeBandWidth:100Mbps=>500Mbps'."\n";	
			}
		}elseif($Traffic < $this->minLimit AND $Traffic != 0){
			if($this->BandWidthMbps == 500 ){
				$this->changeBandWidth($this->routerID,100);
				echo '['.date("Y:m:d H:i:s").']changeBandWidth:500Mbps=>100Mbps'."\n";	
			}
		}else{
			return false;
		}
	}


	public function getRouterID(){
		$result = file_get_contents($this->getBaseURL());
		$array  = json_decode($result,false);
		$data['name']   = $array->Internet[0]->Name;
		$data['id'] = $array->Internet[0]->ID;
		$data['BandWidthMbps'] = $array->Internet[0]->BandWidthMbps;
		$this->routerID = $data['id'] ;
		$this->BandWidthMbps = $data['BandWidthMbps'] ;
		return $data;
	}	

	public function getTraffic(){
		$router = $this->getRouterID(); 
		$result = file_get_contents($this->getBaseURL().'/'.$router['id'].'/monitor');
		$array  = json_decode($result,true);	
		if($array['is_ok'] == 1){
			return $array['Data'];
		}else{
			return false;
		}
	}

	public function checkTraffic(){
		$TrafficArray = $this->getTraffic();
		foreach($TrafficArray as $v){
			if(!empty($v['Out'])){
				$out[] = $v['Out'];
			}
		}

		if(empty($out)){
			return 0;
		}

		//直近何件取得するか
		$i = 5;
		$cnt = $i;
		$sum = 0;

		$arrayCount = count($out) - 1 ;

		//帯域変更を行なうと、Traffic統計が一旦消えるため、正常に集計できない。その場合は、０を返却
		if($arrayCount < $i){
			return 0;
		}

		while($i!=0){
			$num = $arrayCount - $i;
			$sum += $out[$num];
			$i--;
		}
		//Mbps	
		$ave  = intval($sum/$cnt/1024/1024);
		return $ave;
	}

	public function changeBandWidth($id,$band=100){
		$dataJson = json_encode(array('Internet' => array('BandWidthMbps' => $band)));
		$context  = stream_context_create(
			array(
				'http' => array(
					'method'=> 'PUT',
					'header'=> 'Content-type: application/json; charset=UTF-8',
					'content' => $dataJson
				)
			)
		);

		return file_get_contents($this->getBaseURL().'/'.$id.'/bandwidth',false, $context);

	}



}



