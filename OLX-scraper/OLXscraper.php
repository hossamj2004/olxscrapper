<?php
/**
* Copyright (c) 2016, Marcin Walczak
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
* 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

 foreach (glob("lessql/src/LessQL/*.php") as $filename)
{
	
    include $filename;
}

include_once 'simple_html_dom.php';
include_once 'OLXscraper.php';


	
class OLXscraper
{
    public static $ajaxPhone = "http://olx.com.eg/ajax/misc/contact/phone/";
    public static function getOLXSiteData($url){
        $id = substr($url, strpos($url, '-ID')+3, (strpos($url, '.htm')-strpos($url, '-ID')-3));
        $html = file_get_html($url);

        $result=[];
        $result['url'] = $url;
        $result['title'] = trim($html->find('h1.brkword')[0]->plaintext);
        $result['user'] = trim($html->find('.user-box__info__name')[0]->plaintext);
        $result['location'] = trim($html->find('strong.c2b')[0]->plaintext);
        $attributes = $html->find('.item');
        $result['attrs']=[];
        foreach($attributes as $attributes ){
			$result['attrs'][$attributes->find('th')[0]->plaintext] = trim( $attributes->find('strong')[0]->plaintext );
			if( $attributes->find('th')[0]->plaintext == 'موديل'){
				$result['model']=  trim( $attributes->find('strong')[0]->plaintext );
			}
			if( $attributes->find('th')[0]->plaintext == 'السنة'){
				$result['year'] =self::faTOen(trim( $attributes->find('strong')[0]->plaintext ));
			}
		}
        //photo-handler
        $result['images']=[];
        $images = $html->find('.photo-glow');
        foreach($images as $image ){
			$result['images'][] = $image->find('img')[0]->src;
		}
        $result['price'] = floatval( str_replace (',','',$html->find('.xxxx-large')[0]->plaintext) ) ;	
        $result['description'] = trim($html->find('#textContent')[0]->plaintext);
        try{
            $content = @file_get_contents(OLXscraper::$ajaxPhone.$id);
            $result['phone'] = preg_replace('/[^0-9,]|,[0-9]*$/','', json_decode($content, true)['value']);
        }
        catch(Exception $e){
            $result['phone'] = null;
        }
		 $result['created_at']=date("Y-m-d H:i:s");
        return $result;
    }
	
    public static function getOLXLinkList($url, $pageFrom, $pageTo){
        $result = [];
		$list = [];
		$urlArr = explode('?',$url);
		
        for($i=$pageFrom; $i<=$pageTo; $i++){
        	
	        $url=$urlArr[0];
			$postdata =$urlArr[1];
			$opts = array('http' =>
			    array(
			        'method'  => 'POST',
			        'header'  => 'Content-type: application/x-www-form-urlencoded',
			        'content' => $postdata
			    )
			);
			$context  = stream_context_create($opts);
			
            $html = file_get_html($url,false,$context);
            $list = array_merge($list, $html->find('a.ads__item__title'));
        }
		
        foreach($list as $link){
			if(!(in_array($link->href, $result))) $result[] = $link->href;
        }
        return $result;
    }
	
    public static function getOLXListData($url, $pageFrom, $pageTo, $unique){
        $result = [];
        $links = OLXscraper::getOLXLinkList($url, $pageFrom, $pageTo);
        foreach($links as $link){
       
			$db=OLXscraper::initDB();
			$ads = $db->ad()
				->where( 'url', $link )
				->orderBy( 'id', 'DESC' )
				; 

			if( $ads->count() > 0 ) continue;
			$record = OLXscraper::getOLXSiteData($link);
			if($unique){
				$phones = array_column($result, 'phone');
				if(!(in_array($record['phone'], $phones))) $result[] = $record;
			}
			else $result[] = $record;
			
			//return $result;
        }
        return $result;
    }
    
	function pp($arr){
	    $retStr = '<ul>';
	    if (is_array($arr)){
	        foreach ($arr as $key=>$val){
	            if (is_array($val)){
	                $retStr .= '<li>' . $key . ' => ' .OLXscraper:: pp($val) . '</li>';
	            }else{
	                $retStr .= '<li>' . $key . ' => ' . $val . '</li>';
	            }
	        }
	    }
	    $retStr .= '</ul>';
	    return $retStr;
	}

	public static function initDB(){
		 $pdo = new PDO(
		    'mysql:host=localhost;dbname=olx_scrap;charset=utf8;',
		    'root',
		    '');
		   
		$db = new \LessQL\Database( $pdo );
		
		return $db;
	}
	public function faTOen($string) {
		return strtr($string, array('۰'=>'0', '۱'=>'1', '۲'=>'2', '۳'=>'3', '۴'=>'4', '۵'=>'5', '۶'=>'6', '۷'=>'7', '۸'=>'8', '۹'=>'9', '٠'=>'0', '١'=>'1', '٢'=>'2', '٣'=>'3', '٤'=>'4', '٥'=>'5', '٦'=>'6', '٧'=>'7', '٨'=>'8', '٩'=>'9'));
	}
	
}