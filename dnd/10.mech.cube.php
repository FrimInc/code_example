<?php

namespace MECH;

Class Cube
{

	public static function Simple($count = 1, $diap = 6, $Thrower = null, $comment = '')
	{
		$CUBES1 = [];
		for ($i = 0; $i < $count; $i++) {
			$CUBES1[] = rand(1, $diap);
		}
		sort($CUBES1, SORT_NUMERIC);
		$CUBES = '';
		foreach ($CUBES1 as $cval) {
			$CUBES .= 'KA:' . $diap . ':' . $cval . ' ';
		}
		LC($comment . $CUBES);
		return max($CUBES1);
	}

	/**
	 * @param $d1 1d20
	 * @param $FROM obj=obj, [stats=>[[statname=>stattype],skills=>]
	 * @param $d2 d20
	 * @param $TO obj=obj, [stats=>stats,skills=>]
	 */
	public static function T($d1 = '1d20', $FROM, $d2 = '1d20', $TO, $actionName_ = [])
	{
		if (!is_array($actionName_)) {
			$actionName_ = [$actionName_];
		}
		$actionName = $actionName_[count($actionName_) - 1];
		$val = 0;
		$val2 = 0;
		$ard1 = explode('d', $d1);
		$ard2 = explode('d', $d2);
		$count1 = $ard1[0];
		$diap1 = $ard1[1];
		$count2 = $ard2[0];
		$diap2 = $ard2[1];

		$CUBES1 = [];
		$CUBES2 = [];
		$CUBES = '';


		if (is_object($from = $FROM['obj'])) {
			if (is_array($FROM['stats'])) {
				foreach ($FROM['stats'] as $statname => $type) {
					if (is_numeric($curStat = $from->SPECIAL[$statname])) {
						$type = explode(":::", $type);
						switch ($type[0]) {
							case 'count':
								$count1 += $from->SPECIAL[$statname];
								break;
							case 'diap':
								$diap1 += $from->SPECIAL[$statname];
								break;
							case 'countd':
								$count1 += floor($from->SPECIAL[$statname] / $type[1]);
								break;
							case 'diapd':
								$diap1 += floor($from->SPECIAL[$statname] / $type[1]);
								break;
						}
					}
				}
			}

			// проверяем скилы бросающего
			foreach ($FROM['obj']->SKILLS as $skillname1 => $curSkill1) {
				if (is_object($curSkill1 = $from->SKILLS[$skillname1])) {
					if (method_exists($curSkill1, $Method = 'BeforeCube_' . $actionName . '_DO')) {
						list($count1, $diap1) = $curSkill1->$Method($count1, $diap1, $TO['obj']);
					}
					if (is_array($curSkill1->LINKS_TO) && ($linkTO = $curSkill1->LINKS_TO[$actionName]) && method_exists($curSkill1, $Method = 'BeforeLink_' . $linkTO . '_DO')) {
						list($count1, $diap1) = $curSkill1->$Method($count1, $diap1, $TO['obj']);
					}
				}
			}

			// проверяем скилы бросающего на точи цели
			foreach ($FROM['obj']->SKILLS as $skillname1 => $curSkill1) {
				foreach ($TO['obj']->TOUCHES as $touchName) {
					if (is_object($curSkill1 = $from->SKILLS[$skillname1]) && method_exists($curSkill1, $Method = 'BeforeCube_Touch_' . $touchName . '_DO')) {
						list($count1, $diap1) = $curSkill1->$Method($count1, $diap1, $TO['obj']);
					}
				}
			}

			for ($_t = 0; $_t <= $count1; $_t++) {
				$val = max([$val, $T = rand(1, $diap1)]);
				$CUBES1[] = $T;
			}

			// проверяем скилы бросающего модификатор броска
			foreach ($FROM['obj']->SKILLS as $skillname1 => $curSkill1) {
				if (is_object($curSkill1 = $from->SKILLS[$skillname1])) {
					if (method_exists($curSkill1, $Method = 'OnCube_' . $actionName . '_DO')) {
						$val = $curSkill1->$Method($val, $TO['obj']);
					}
				}
			}


			sort($CUBES1, SORT_NUMERIC);
			foreach ($CUBES1 as $cval) {
				$CUBES .= 'KA:' . $diap1 . ':' . $cval . ' ';
			}


		}

		if (is_object($to = $TO['obj'])) {
			if (is_array($TO['stats'])) {
				foreach ($TO['stats'] as $statname2 => $type2) {
					if (is_numeric($curStat = $to->SPECIAL[$statname2])) {
						switch ($type2) {
							case 'count':
								$count2 += $to->SPECIAL[$statname2];
								break;
							case 'diap':
								$diap2 += $to->SPECIAL[$statname2];
								break;
						}
					}
				}
			}

			//проверяем скилы цели
			foreach ($TO['obj']->SKILLS as $skillname2 => $curSkill2) {
				if (is_object($curSkill2 = $to->SKILLS[$skillname2])) {
					if (method_exists($curSkill2, $Method = 'BeforeCube_' . $actionName . '_GET')) {
						list($count2, $diap2) = $curSkill2->$Method($count2, $diap2, $FROM['obj']);
					}
					if (is_array($curSkill2->LINKS_FROM) && ($linkFROM = $curSkill2->LINKS_FROM[$actionName]) && method_exists($curSkill2, $Method = 'BeforeLink_' . $linkFROM . '_GET')) {
						list($count2, $diap2) = $curSkill2->$Method($count2, $diap2, $FROM['obj']);
					}
				}
			}

			// проверяем скилы цели на точи бросающего
			foreach ($TO['obj']->SKILLS as $skillname1 => $curSkill1) {
				foreach ($FROM['obj']->TOUCHES as $touchName) {
					if (is_object($curSkill1 = $to->SKILLS[$skillname1]) && method_exists($curSkill1, $Method = 'BeforeCube_Touch_' . $touchName . '_GET')) {
						list($count2, $diap2) = $curSkill1->$Method($count2, $diap2, $FROM['obj']);
					}
				}
			}

			for ($_t = 0; $_t <= $count2; $_t++) {
				$val2 = max([$val2, $T = rand(1, $diap2)]);
				$CUBES2[] = $T; //' KD:' . $diap2 . ':' . $T;
			}

			// проверяем скилы цели модификатор броска
			foreach ($TO['obj']->SKILLS as $skillname2 => $curSkill2) {
				if (is_object($curSkill2 = $to->SKILLS[$skillname2]) && method_exists($curSkill2, $Method = 'OnCube_' . $actionName . '_GET')) {
					$val2 = $curSkill2->$Method($val2, $FROM['obj']);
				}
			}

			sort($CUBES2, SORT_NUMERIC);
			$CUBES2 = array_reverse($CUBES2);
			foreach ($CUBES2 as $cval) {
				$CUBES .= 'KD:' . $diap2 . ':' . $cval . ' ';
			}
		}

		LC($CUBES);

		//проверяем последствия броска для бросающего
		foreach ($FROM['obj']->SKILLS as $skillname1 => $curSkill1) {
			//проверяем скилы бросающего
			if (is_object($curSkill1)) {
				if (method_exists($curSkill1, $Method = 'AfterCube_' . $actionName . '_DO')) {
					$val += $curSkill1->$Method($val, $val2, $TO['obj']);
				}
				if (is_array($curSkill1->LINKS_FROM) && ($linkTO = $curSkill1->LINKS_FROM[$actionName]) && method_exists($curSkill1, $Method = 'AfterLink_' . $linkTO . '_DO')) {
					$val += $curSkill1->$Method($val, $val2, $TO['obj']);
				}

				//проверяем скилы бросающего на точи цели
				foreach ($curSkill1->TOUCHES as $touchName) {
					foreach ($TO['obj']->SKILLS as $skillname2 => $curSkill2) {
						if (is_object($curSkill2 = $to->SKILLS[$skillname2]) && method_exists($curSkill2, $Method = 'AfterCube_Touch_' . $touchName . '_GET')) {
							$val2 += $curSkill2->$Method($val2, $val, $FROM['obj']);
						}
					}
				}

			}
		}

		//проверяем скилы итема бросающего на точи цели
		if (is_object($FROM['item'])) {
			foreach ($FROM['item']->TOUCHES as $touchName) {
				foreach ($TO['obj']->SKILLS as $skillname2 => $curSkill2) {
					if (is_object($curSkill2 = $to->SKILLS[$skillname2]) && method_exists($curSkill2, $Method = 'AfterCube_Touch_' . $touchName . '_GET')) {
						$val2 += $curSkill2->$Method($val2, $val, $FROM['item']);
					}
				}
			}
		}

		//проверяем последствия броска для цели
		foreach ($TO['obj']->SKILLS as $skillname1 => $curSkill1) {
			//проверяем скилы цели
			if (is_object($curSkill1 = $TO['obj']->SKILLS[$skillname1])) {
				if (method_exists($curSkill1, $TOthod = 'AfterCube_' . $actionName . '_GET')) {
					$val += $curSkill1->$TOthod($val, $val2, $FROM['obj']);
				}

				if (is_array($curSkill1->LINKS_TO) && ($linkTO = $curSkill1->LINKS_TO[$actionName]) && method_exists($curSkill1, $Method = 'AfterLink_' . $linkTO . '_GET')) {
					$val += $curSkill1->$Method($val, $val2, $TO['obj']);
				}

				//проверяем скилы цели на точи бросающего
				foreach ($curSkill1->TOUCHES as $FROMuchName) {
					foreach ($FROM['obj']->SKILLS as $skillname2 => $curSkill2) {
						if (is_object($curSkill2 = $FROM['obj']->SKILLS[$skillname2]) && method_exists($curSkill2, $TOthod = 'AfterCube_Touch_' . $FROMuchName . '_DO')) {
							$val2 += $curSkill2->$TOthod($val2, $val, $TO['obj']);
						}
					}
				}

				//проверяем скилы итема цели на точи бросающего
				if (is_object($TO['item'])) {
					foreach ($TO['item']->TOUCHES as $FROMuchName) {
						foreach ($FROM['obj']->SKILLS as $skillname2 => $curSkill2) {
							if (is_object($curSkill2 = $FROM['obj']->SKILLS[$skillname2]) && method_exists($curSkill2, $TOthod = 'AfterCube_Touch_' . $FROMuchName . '_GET')) {
								$val2 += $curSkill2->$TOthod($val2, $val, $TO['item']);
							}
						}
					}

				}
			}
		}

		foreach ($actionName_ as $_actionName) {

			if (method_exists($TO['obj'], $Method = 'After' . $_actionName)) {
				$TO['obj']->$Method($val, $val2, $FROM['obj']);
			}
		}

		return [$val - $val2, $val, $val2];

	}

}