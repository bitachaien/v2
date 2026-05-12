<?php
namespace Lib;
class wanfa_fadan {
	
	function getplayers($typeid){
		$playrules = [];
		$_wfs = $this->$typeid();
		if(!method_exists($this,$typeid)){
			return false;
		}
		foreach($_wfs as $k=>$v){
			foreach($v['list'] as $k1=>$v1){
				$playrules[$v1['playid']] = $v1;
			}
		}
		return $playrules;
	}
	function xy28(){
		$tms = [];
		for($i=0;$i<=27;$i++){
			
			$tms[] = ['playid'=>'xy28_tm_'.str_pad($i,2,0,STR_PAD_LEFT),'rate'=>1,'title'=>$i];
		}
		$xy28 = [
			'tm'=>[
				'title'=>'特码',
				'list'=>$tms
			],
			'hunhe'=>[
				'title'=>'混合',
				'list'=>[
					['playid'=>'xy28_hunhe_big','rate'=>1,'title'=>'大'],
					['playid'=>'xy28_hunhe_small','rate'=>1,'title'=>'小'],
					['playid'=>'xy28_hunhe_odd','rate'=>1,'title'=>'单'],
					['playid'=>'xy28_hunhe_even','rate'=>1,'title'=>'双'],
					['playid'=>'xy28_hunhe_big_odd','rate'=>1,'title'=>'大单'],
					['playid'=>'xy28_hunhe_small_odd','rate'=>1,'title'=>'小单'],
					['playid'=>'xy28_hunhe_big_even','rate'=>1,'title'=>'大双'],
					['playid'=>'xy28_hunhe_small_even','rate'=>1,'title'=>'小双'],
					['playid'=>'xy28_hunhe_ji_big','rate'=>1,'title'=>'极大'],
					['playid'=>'xy28_hunhe_ji_small','rate'=>1,'title'=>'极小'],
				],
			],
		];
		return $xy28;
	}
	function keno(){
		$zhuzis = [];
		for($i=1;$i<=80;$i++){
			$zhuzi = str_pad($i,2,0,STR_PAD_LEFT);
			$zhuzis[] = ['playid'=>'keno_zhuzi_'.$zhuzi,'title'=>$zhuzi];
		}
		$klc = [
			'rx1'=>[
				'title'=>'任选一',
				'list'=>[
					['playid'=>'bjkl8rx1','title'=>'任选一'],
				],
			],
			'rx2'=>[
				'title'=>'任选二',
				'list'=>[
					['playid'=>'bjkl8rx2','title'=>'任选二'],
				],
			],
		];
		return $klc;
	}
	function lhc(){
		$lhc = [
			'tmzx'=>[
				'title'=>'特码直选',
				'list'=>[
					['playid'=>'tmzx','rate'=>'48.51','title'=>'直选A'],
                    ['playid'=>'tmzx2','rate'=>'42.00','title'=>'直选B'],
				],
			],
			'tmlm'=>[
				'title'=>'特码两面',
				'list'=>[
					['playid'=>'tmlmda',          'rate'=>'1.980','title'=>'特码大'],
					['playid'=>'tmlmxiao',        'rate'=>'1.980','title'=>'特码小'],
					['playid'=>'tmlmdan',         'rate'=>'1.980','title'=>'特码单'],
					['playid'=>'tmlmshuang',      'rate'=>'1.980','title'=>'特码双'],
					['playid'=>'tmlmdadan',       'rate'=>'3.960','title'=>'特码大单'],
					['playid'=>'tmlmdashuang',    'rate'=>'3.960','title'=>'特码大双'],
					['playid'=>'tmlmxiaodan',     'rate'=>'3.960','title'=>'特码小单'],
					['playid'=>'tmlmxiaoshuang',  'rate'=>'3.960','title'=>'特码小双'],
					['playid'=>'tmlmheda',        'rate'=>'1.980','title'=>'特码合大'],
					['playid'=>'tmlmhexiao',      'rate'=>'1.980','title'=>'特码合小'],
					['playid'=>'tmlmhedan',       'rate'=>'1.980','title'=>'特码合单'],
					['playid'=>'tmlmheshuang',    'rate'=>'1.980','title'=>'特码合双'],
					['playid'=>'tmlmweida',       'rate'=>'1.980','title'=>'特码尾大'],
					['playid'=>'tmlmweixiao',     'rate'=>'1.980','title'=>'特码尾小'],
					['playid'=>'tmlmjiaqin',      'rate'=>'1.901','title'=>'特码家禽'],
					['playid'=>'tmlmyeshou',      'rate'=>'1.980','title'=>'特码野兽'],
					['playid'=>'tmlmhongbo',      'rate'=>'2.795','title'=>'特码红波'],
					['playid'=>'tmlmlvbo',        'rate'=>'2.970','title'=>'特码绿波'],
					['playid'=>'tmlmlanbo',       'rate'=>'2.970','title'=>'特码蓝波'],
				],
			],
			
			'sxtx'=>[
				'title'=>'特肖',
				'list'=>[
					['playid'=>'sxtxshu',  'rate'=>'11.63','title'=>'特肖鼠'],
					['playid'=>'sxtxniu',  'rate'=>'11.63','title'=>'特肖牛'],
					['playid'=>'sxtxhu',   'rate'=>'11.63','title'=>'特肖虎'],
					['playid'=>'sxtxtu',   'rate'=>'11.63','title'=>'特肖兔'],
					['playid'=>'sxtxlong', 'rate'=>'11.63','title'=>'特肖龙'],
					['playid'=>'sxtxshe',  'rate'=>'11.63','title'=>'特肖蛇'],
					['playid'=>'sxtxma',   'rate'=>'11.63','title'=>'特肖马'],
					['playid'=>'sxtxyang', 'rate'=>'11.63','title'=>'特肖羊'],
					['playid'=>'sxtxhou',  'rate'=>'11.63','title'=>'特肖猴'],
					['playid'=>'sxtxji',   'rate'=>'9.31','title'=>'特肖鸡'],
					['playid'=>'sxtxgou',  'rate'=>'11.63','title'=>'特肖狗'],
					['playid'=>'sxtxzhu',  'rate'=>'11.63','title'=>'特肖猪'],
				],
			],
			


		];
		return $lhc;
	}
	function dpc(){
		$dp3 = [
			'x3'=>[
				'title'=>'直选',
				'list'=>[
					['playid'=>'pl3zxfs','title'=>'三星直选复式'],
					['playid'=>'pl3zxds','title'=>'三星直选单式'],
				],
			],
			'zx'=>[
				'title'=>'组选',
				'list'=>[
					['playid'=>'pl3zux3','title'=>'三星组三'],
					['playid'=>'pl3zux6','title'=>'三星组六'],
					['playid'=>'pl3zuxhh','title'=>'三星混合组选'],

					['playid'=>'pl3zuxbd','title'=>'三星组选包胆'],
					['playid'=>'pl3zsds','title'=>'三星组三单式'],
					['playid'=>'pl3zlds','title'=>'三星组六单式'],
					['playid'=>'pl3q2zxfs','title'=>'前二组选复式'],
					['playid'=>'pl3q2zxds','title'=>'前二组选单式'],
					['playid'=>'pl3q2zxbd','title'=>'前二组选包胆'],
					['playid'=>'pl3h2zxfs','title'=>'后二组选复式'],
					['playid'=>'pl3h2zxds','title'=>'后二组选单式'],
					['playid'=>'pl3h2zxbd','title'=>'后二组选包胆'],
				],
			],
			'x2'=>[
				'title'=>'二星',
				'list'=>[
					['playid'=>'pl3qx2fs','title'=>'前二直选复式'],
					['playid'=>'pl3qx2ds','title'=>'前二直选单式'],
					['playid'=>'pl3hx2fs','title'=>'后二直选复式'],
					['playid'=>'pl3hx2ds','title'=>'后二直选单式'],
				],
			],
			'bdw'=>[
				'title'=>'不定位',
				'list'=>[
					['playid'=>'pl3ymbdw','title'=>'三星一码不定位'],

					['playid'=>'pl3rmbdw','title'=>'三星二码不定位'],
					['playid'=>'pl3kd','title'=>'三星跨度'],
					['playid'=>'pl3q2kd','title'=>'前二跨度'],
					['playid'=>'pl3h2kd','title'=>'后二跨度'],
				],
			],
			'dw'=>[
				'title'=>'定位胆',
				'list'=>[
					['playid'=>'pl3dwdfs','title'=>'复式'],

				],
			],
			'hz'=>[
				'title'=>'和值',
				'list'=>[
					['playid'=>'pl3hzzx','title'=>'三星直选和值'],

					['playid'=>'pl3zuxhz','title'=>'三星组选和值'],
					['playid'=>'pl3q2zxhz','title'=>'前二直选和值'],
					['playid'=>'pl3q2zuxhz','title'=>'前二组选和值'],
					['playid'=>'pl3h2zxhz','title'=>'后二直选和值'],
					['playid'=>'pl3h2zuxhz','title'=>'后二组选和值'],
				],
			],
			'dxds'=>[
				'title'=>'大小单双',
				'list'=>[
					['playid'=>'dxdsq2','title'=>'前二大小单双'],
					['playid'=>'dxdsh2','title'=>'后二大小单双'],
				],
			],

		];
		return $dp3;
	}
	function pk10(){
		$pk10 = [
			'qian1'=>[
				'title'=>'前一',
				'list'=>[
					['playid'=>'bjpk10qian1','title'=>'前一复式'],
				],
			],
			'qian2'=>[
				'title'=>'前二',
				'list'=>[
					['playid'=>'bjpk10qian2','title'=>'前二复式'],
					['playid'=>'bjpk10qian2ds','title'=>'前二单式'],
				],
			],
			'qian3'=>[
				'title'=>'前三',
				'list'=>[
					['playid'=>'bjpk10qian3','title'=>'前三复式'],
					['playid'=>'bjpk10qian3ds','title'=>'前三单式'],
				],
			],
			'qian4'=>[
				'title'=>'前四',
				'list'=>[
					['playid'=>'bjpk10qian4','title'=>'前四复式'],
					['playid'=>'bjpk10qian4ds','title'=>'前四单式'],
				],
			],
			'qian5'=>[
				'title'=>'前五',
				'list'=>[
					['playid'=>'bjpk10qian5','title'=>'前五复式'],
					['playid'=>'bjpk10qian5ds','title'=>'前五单式'],
				],
			],
			'dwd'=>[
				'title'=>'定位胆',
				'list'=>[
					['playid'=>'bjpk10dwd','title'=>'定位胆'],
				],
			],
			
		];
		return $pk10;
	}
	function k3(){
		$k3 = [
		
			'sthtx'=>[
				'title'=>'三同号通选',
				'list'=>[
					['playid'=>'k3sthtx','rate'=>'36.5','title'=>'三同号通选'],
				],
			],
			
			'slhtx'=>[
				'title'=>'三连号通选',
				'list'=>[
					['playid'=>'k3slhtx','rate'=>'8.5','title'=>'三连号通选'],
				],
			],
			
			'k3hzzx'=>[
				'title'=>'和值',
				'list'=>[

					['playid'=>'k3hz3','rate'=>'165','title'=>'3'],
					['playid'=>'k3hz4','rate'=>'60','title'=>'4'],
					['playid'=>'k3hz5','rate'=>'32.5','title'=>'5'],
					['playid'=>'k3hz6','rate'=>'20.5','title'=>'6'],
					['playid'=>'k3hz7','rate'=>'12.5','title'=>'7'],
					['playid'=>'k3hz8','rate'=>'9.5','title'=>'8'],
					['playid'=>'k3hz9','rate'=>'8.5','title'=>'9'],
					['playid'=>'k3hz10','rate'=>'7.5','title'=>'10'],
					['playid'=>'k3hz11','rate'=>'7.5','title'=>'11'],
					['playid'=>'k3hz12','rate'=>'8.5','title'=>'12'],
					['playid'=>'k3hz13','rate'=>'9.5','title'=>'13'],
					['playid'=>'k3hz14','rate'=>'12.5','title'=>'14'],
					['playid'=>'k3hz15','rate'=>'20.5','title'=>'15'],
					['playid'=>'k3hz16','rate'=>'32.5','title'=>'16'],
					['playid'=>'k3hz17','rate'=>'60','title'=>'17'],
					['playid'=>'k3hz18','rate'=>'165','title'=>'18'],
					['playid'=>'k3hzbig','rate'=>'1.95','title'=>'大'],
					['playid'=>'k3hzsmall','rate'=>'1.95','title'=>'小'],
					['playid'=>'k3hzodd','rate'=>'1.95','title'=>'单'],
					['playid'=>'k3hzeven','rate'=>'1.95','title'=>'双'],
				],
			],
		];
		return $k3;
	}
	function x5(){
		$x5 = [
			
			'bdw'=>[
				'title'=>'不定位',
				'list'=>[
					['playid'=>'x5bdwqs','title'=>'前三不定位'],
				],
			],
			'dw'=>[
				'title'=>'定位胆',
				'list'=>[
					['playid'=>'x5dwd','title'=>'定位胆'],
				],
			],


		];
		return $x5;
	}
	function ssc(){
		$ssc = [

			'2x'=>[
				'title'=>'二星',
				'list'=>[
					['playid'=>'exzhixfsq','title'=>'前二直选复式'],
					['playid'=>'exzhixfsh','title'=>'后二直选复式'],

				],
			],
			'dw'=>[
				'title'=>'定位胆',
				'list'=>[
					['playid'=>'dweid','title'=>'一星复式'],

				],
			],


			'dxds'=>[
				'title'=>'大小单双',
				'list'=>[
					['playid'=>'dxdsqe','title'=>'前二大小单双'],
					['playid'=>'dxdshe','title'=>'后二大小单双'],
				],
			],

		];
		return $ssc;
	}
	function kl10f(){
		$sf = [
			'dwd'=>[
				'title'=>'定位胆',
				'list'=>[
					['playid'=>'kl10dwd1','title'=>'第一位'],
					['playid'=>'kl10dwd2','title'=>'第二位'],
					['playid'=>'kl10dwd3','title'=>'第三位'],
					['playid'=>'kl10dwd4','title'=>'第四位'],
					['playid'=>'kl10dwd5','title'=>'第五位'],
					['playid'=>'kl10dwd6','title'=>'第六位'],
					['playid'=>'kl10dwd7','title'=>'第七位'],
					['playid'=>'kl10dwd8','title'=>'第八位'],
				],
			],
			'rx'=>[
				'title'=>'任选',
				'list'=>[
					['playid'=>'kl10rx1z1','title'=>'一中一'],
					['playid'=>'kl10rx2z2','title'=>'二中二'],
					['playid'=>'kl10rx3z3','title'=>'三中三'],
					['playid'=>'kl10rx4z4','title'=>'四中四'],
					['playid'=>'kl10rx5z5','title'=>'五中五'],
				],
			],
			'dt'=>[
				'title'=>'胆拖',
				'list'=>[
					['playid'=>'kl10dt2z2','title'=>'二中二'],
					['playid'=>'kl10dt3z3','title'=>'三中三'],
					['playid'=>'kl10dt4z4','title'=>'四中四'],
					['playid'=>'kl10dt5z5','title'=>'五中五'],
				],
			],
			'x3'=>[
				'title'=>'三星',
				'list'=>[
					['playid'=>'kl10qszxfs','title'=>'前三直选'],
					['playid'=>'kl10hszxfs','title'=>'后三直选'],
					['playid'=>'kl10qszux','title'=>'前三组选'],
					['playid'=>'kl10hszux','title'=>'后三组选'],
				],
			],
			'x2'=>[
				'title'=>'二星',
				'list'=>[
					['playid'=>'kl10elzx','title'=>'二连直选'],
					['playid'=>'kl10elzux','title'=>'二连组选'],
				],
			],
		];
		return $sf;
	}
}
?>