<?php
class Fenge{
    public $wordsLen = 38; //最大查询字数
    public $douHao = '，';
    public $juHao = '。';
    public $fenDuan = 1; //是否分段分割[0=>不分段，1=>分段]
    public $fenDuanFlag = '||||||'; //分段标识符
    public $minCheckStrLen = 10; //最少需要查询的字数

    //初始化
    public function __construct($params = []) {
        $this->wordsLen = isset($params['wordsLen']) ? intval($params['wordsLen']) : $this->wordsLen;
        $this->fenDuan = isset($params['fenDuan']) ? intval($params['fenDuan']) : $this->fenDuan;
        $this->minCheckStrLen = isset($params['minCheckStrLen']) ? intval($params['minCheckStrLen']) : $this->minCheckStrLen;
        $this->douHao = isset($params['douHao']) ? $params['douHao'] : $this->douHao;
        $this->juHao = isset($params['juHao']) ? $params['juHao'] : $this->juHao;
        $this->fenDuanFlag = isset($params['fenDuanFlag']) ? $params['fenDuanFlag'] : $this->fenDuanFlag;
    }

    public function handleContent($content){
        $trueContent = strip_tags($content);
        $trueContent = htmlspecialchars_decode($trueContent);
        $trueContent = trim(str_replace(array('&nbsp;','　'), '',$trueContent));
        $trueContent = trim(str_replace(array('.'), $this->juHao,$trueContent));
        $trueContent = trim(str_replace(array(','), $this->douHao,$trueContent));
        $trueContent = preg_replace('/。。/', $this->juHao, $trueContent);
        $trueContent = preg_replace('/，/', $this->juHao, $trueContent);
        $endArr = [];
        if($this->fenDuan){
            $trueContent = preg_replace('/[\r\n]+/', $this->fenDuanFlag, $trueContent);
            $trueContentArr = explode($this->fenDuanFlag,$trueContent);
            foreach ($trueContentArr as $key => $value) {
                $endArr = array_merge_recursive($endArr, $this->segmFirst($value));
            }
        }else{
            $trueContent = preg_replace('/[\r\n]+/', $this->juHao, $trueContent);
            $endArr = $this->segmFirst($trueContent);
        }
        //var_dump($trueContent,$endArr, strlen($trueContent), strlen(implode('',$endArr)));exit;
        return self::filterMinStrLen($endArr, $this->minCheckStrLen);
    }

    public static function filterMinStrLen($handleArr = [], $minCheckStrLen){
        $filterArr = [];
        if($handleArr){
            foreach ($handleArr as $key => $value) {
                if(self::mbStrLenUtf8($value) >= $minCheckStrLen){
                    $filterArr[] = $value;
                }
            }
        }
        return $filterArr;
    }

    public function segmFirst($trueContent){
        $trueContentPartArr = explode($this->juHao,$trueContent);
        return $this->segm($trueContentPartArr);
    }

    public function segm($inArr, $outArr =[], $tmpStr = ''){
        //var_dump($inArr,$outArr);exit;
        if($inArr){
            foreach($inArr as $key => $value){
                $tmpStrLen = self::mbStrLenUtf8($tmpStr);
                $valueLen = self::mbStrLenUtf8($value);
                //var_dump([$tmpStrLen=>$tmpStr],[$valueLen=>$value]);exit;
                if($valueLen >= $this->wordsLen){
                    if($tmpStrLen){
                        array_push($outArr, $tmpStr);
                    }
                    $outArr = array_merge_recursive($outArr, self::mbStringToArray($value, $this->wordsLen));
                    $tmpStr = '';
                }else{
                    $addTmpStr = $tmpStr ? $tmpStr.',' : $tmpStr;
                    $addTmpLen = self::mbStrLenUtf8($addTmpStr);
                    $sumLen = $addTmpLen + $valueLen;
                    if($sumLen == $this->wordsLen){
                        array_push($outArr, $addTmpStr.$value);
                        $tmpStr = '';
                    }else if($sumLen > $this->wordsLen){
                        array_push($outArr, $tmpStr);
                        $tmpStr = $value;
                    }else{
                        $tmpStr = $addTmpStr.$value;
                    }
                }
                unset($inArr[$key]);
                break;
            }
            $inArr = array_merge($inArr, []);
            $outArr = array_merge($outArr, []);
            //var_dump($inArr,$outArr,$tmpStr);exit;
            return $this->segm($inArr,$outArr,$tmpStr);
        }else{
            if( $tmpStr ){
                array_push($outArr, $tmpStr);
            }
        }
        $outArr = array_merge($outArr, []);
        return $outArr;
    }

    public static function mbStrLenUtf8($str){
        return mb_strlen($str, 'utf-8');
    }

    //按指定字长分割字符串为数组
    public static function mbStringToArray($string, $len) {
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string,0,$len,"UTF-8");
            $string = mb_substr($string,$len,$strlen,"UTF-8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }
}
