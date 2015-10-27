<?php

class db{
	
	private static $pattern = '/\!pre\!/';
	
	private static $conLink;
	private static $con;
	
	private static $numSqls = array('UPDATE','REPLACE','DELETE');
	private static $selectSql = 'SELECT';
	private static $insertSql = 'INSERT';
	
	private static $pdoTypes = array('integer'=>PDO::PARAM_INT,'int'=>PDO::PARAM_INT,'string'=>PDO::PARAM_STR,'str'=>PDO::PARAM_STR,'boolean'=>PDO::PARAM_BOOL);
	
	/* */
	
	private static $usuario = null;
	private static $link = null ;

    private static function getLink ($configs) {
	
		$dsn = $configs['engine'].":dbname=".$configs['database'].";host=".$configs['host'].";charset=utf8";

        if ( self :: $link && self :: $usuario == $configs['login']) {
            return self :: $link ;
        }
		
		self :: $usuario = $configs['login'];
		self :: $link = new PDO ( $dsn, $configs['login'], $configs['password']) ;
		return self :: $link ;
    }	
	
	public static function sql($string,$array = array(),$configs = ''){
		$configs = ($configs == '' || $configs == null)?Config::$db['default']: $configs;
		$configs = (is_string($configs))?Config::$db[$configs]: $configs;

		self :: getLink($configs);
		$string = preg_replace(self::$pattern, $configs['pre'], $string);
		//print_r($string);
		$tipo = array_shift(explode(" ",$string));
	
		if(count($array)==0){
			if($tipo == self::$selectSql){
				//$return = self :: $link ->query($string);
				$st = self::$link->prepare($string);
				$resp = $st->execute();
				$return = array();
				while($return[]=$st->fetch(PDO::FETCH_ASSOC));
				array_pop($return);
			}else{
				$return = self :: $link ->exec($string);
			}
		}else{
			$st = self::$link->prepare($string);
						
			foreach($array as $key=>$value){
				$type = (!isset($value[2]) || !in_array($value[2], self::$pdoTypes))?self::$pdoTypes['string']:self::$pdoTypes[$value[2]];
				$st->bindParam($value[0],$value[1],$type);
			}
			
			$resp = $st->execute();
			if($tipo == self::$selectSql){
				$return = array();
				while($return[]=$st->fetch(PDO::FETCH_ASSOC));
				array_pop($return);
				
				//$arr = $st->errorInfo();
			}elseif($tipo == self::$insertSql) {
				$return = self::$link->lastInsertId();
			}else{
				$return = $resp;
			}
		}
		
		return $return;
	}
		
	public static function __callStatic ( $name, $args ) {
        $callback = array ( self :: getLink ( ), $name ) ;
        return call_user_func_array ( $callback , $args ) ;
    }

    public static function previewSQL($string,$array = array(),$configs = ''){
    	$configs = ($configs == '' || $configs == null)?Config::$db['default']: $configs;
		$configs = (is_string($configs))?Config::$db[$configs]: $configs;

    	$string = preg_replace(self::$pattern, $configs['pre'], $string);

		foreach($array as $key=>$value){
			$type = (!isset($value[2]) || in_array($value[2], self::$pdoTypes))?self::$pdoTypes['string']:self::$pdoTypes[$value[2]];
			//$st->bindParam($value[0],$value[1],$type);
			$string = str_replace($value[0], $value[1], $string);
		}

		return $string;
    }
	
	// SQL Methods

    /**
	 * digestWhere
	 *
	 * Prepara uma string com as condi��es para uma consulta sql.
	 *
	 * @version 1.0
	 * @param 	string 	$filter 	Array com informa��es para filtrar os registros
	 * 								- �ndice 0 do array - Nome do campo no banco de dados
	 * 								- �ndice 1 do array - Valor do campo para compara��o
	 * 								- �ndice 2 do array - Operador: =, <, >, <>
	 * 								- �ndice 3 do array - Agregador: OR, AND, AND NOT, OR NOT
	 * 								- �ndice 4 do array - Tipo de campo (string, integer, etc)
	 * @return 	$return 			Array com o sql de condi��es e argumentos
	 */
	public static function digestWhere($filter){
		
		$return = array('sql'=>'','args'=>array());

		if( is_array($filter) && count($filter) > 0 ){
			$filter = (is_array($filter[0]))?$filter:array($filter);
			// loop condicoes
			// [0] atributo,
			// [1] valor,
			// [2] comparador,
			// [3] agregador
			// [4] type
			$condition = " WHERE ";
			$args = array();
			foreach ($filter as $key => $cond) {
				$string = "";
				$string .= $cond[0];

				// se houver valor para comparar
				if(count($cond)>1){
					// se define a forma de compara��o
					$string  .= (count($cond)>2)?" ".trim($cond[2])." ":" = ";
					$sqlKey = ":".preg_replace("/[^a-zA-Z0-9]+/", "", $cond[0]);
					$string  .= $sqlKey;
					$args[] = (count($cond)>4)?array($sqlKey,$cond[1],$cond[4]):array($sqlKey,$cond[1]);
				}
				
				// ignorar caso seja o primeiro
				if($key > 0){
					// se o agragador foi definido
					if( count($cond) > 3 && $cond[3] && in_array($cond[3], array('AND','OR','AND NOT','OR NOT'))){
						$string = $cond[3] . " " . $string;
					}else{
						$string = " AND " . $string;
					}
				}

				$condition .= $string;
			}

			$return = array('sql'=>$condition,'args'=>$args);		
		}

		return $return;
	}

	/**
	 * digestOrder
	 *
	 * Prepara uma string com a ordena��o para uma consulta sql.
	 *
	 * @version 1.0
	 * @param 	string 	$sort 		String ou array com o(s) campo(s) que ser�o utilizados para ordena��o
	 *								- Exemplo de string: $sort = "campo_a";
	 *								- Exemplo de array: $sort = array("campo_a", "campo_b", "campo_c");
	 * @return 	$return 			Array ou string com o sql de ordena��o.
	 */
	public static function digestOrder($sort = false){
		$order  = '';

		if( $sort){
			$order = " ORDER BY ";
			if( is_array($sort) ){
				$order .= implode(", ", $sort);
			}else{
				$order = " ORDER BY " . $sort;
			}			
		}

		return $order;
	}

	/**
	 * digestLimit
	 *
	 * Prepara uma string com os limites para uma consulta sql.
	 *
	 * @version 1.0
	* @param 	string 	$slice 		Array com a linha de in�cio da consulta e com a quantidade de registros por p�gina.
	 *								- �ndice 0 do array - (integer) N�mero da linha para in�cio da consulta
	 *								- �ndice 1 do array - (integer) Quantidade de linhas que ser�o selecionadas na consulta
	 * @return 	$return 			Array ou string com o sql de ordena��o.
	 */
	public static function digestLimit($slice = false){
		
		$limit = '';
		$matchs = array();

		if($slice){
			$limit .= " LIMIT ";
			if( is_array($slice)){
				$matchs[] = array(":limitStart",$slice[0],'int');
				$matchs[] = array(":limitEnd",$slice[1],'int');
				$limit .= ':limitStart , :limitEnd';
			}else{
				$matchs[] = array(":limit",$slice);
				$limit .= ':limit';
			}
		}

		return array('sql'=>$limit,'args'=>$matchs);
	}

	// TODO
	public static function validate($string){
		return true;
	}

}
?>