<?php


trait Simple_AI
{

	var $OD = 10;
	var $area = 10;
	var $follow_dist = 4;

	public function GetActions()
	{
		$this->ViewLineVays = [];
		$this->ViewVays = [];
		$this->ACTION_TILES = [];

		$this->GenerateViews($this->X, $this->Y, [[$this->X, $this->Y]]);
		$this->GenerateMoves($this->X, $this->Y, [[$this->X, $this->Y]]);

		foreach ($this->PROPS as $obProp) {
			foreach (get_class_methods($obProp) as $method_name) {
				if ($method_name === 'CAN_') {

					$obProp->$method_name($ai = true);

				}
			}
		}


	}

	public function AI($justCheck = false)
	{

		$_ = 0;
		if ($this->curr_od > 0) {
			$_++;


			$nearestCells = false;
			$this->AGGRED_TO = null;
			foreach (\MAIN\GAME::$APP->OBJECTS as $_X => $YY) {
				foreach ($YY as $_Y => $obJ) {
					if($obJ->fract=='skip') {
						continue;
					}
					if ($obJ->id !== $this->id && ($this->AGGRED && $obJ->id == $this->AGGRED || !$this->AGGRED && $obJ->fract !== false && count(array_intersect($obJ->fract, $this->fract)) == 0)) {
						$this->AGGRED = $obJ->id;
						$this->AGGRED_TO = $obJ;
						$nearestCells = \Geometry\Geometry::GetNearestCells($obJ->X, $obJ->Y, 15);
						break 2;
					}
				}
			}

			$this->ACTION_TILES = [];
			$this->GetActions();

			$PrevPrior = false;

			if (!$this->AGGRED || !is_object($this->AGGRED_TO)) {
				return;
			}

			$DO_ACTION = false;
			$RESERV_ACTION = false;
			$RESERV_DIST = \Geometry\Geometry::get_distance($this, $this->AGGRED_TO);

			foreach ($this->ACTION_TILES as $_X => $_YY) {
				foreach ($_YY as $_Y => $arAction) {
					foreach ($arAction as $_action => $params) {

						if ($this->ACTIONS_PRIOR[$_action][0] > $PrevPrior) {
							$useProp = false;
							foreach ($this->PROPS as $obProp) {
								if ($obProp->GetActionProvider() == $_action) {
									$useProp = true;
									break;
								}
							}

							if (!$useProp) {
								continue;
							}

							switch ($this->ACTIONS_PRIOR[$_action][1]) {
								case 'direct':
									if (is_object(\MAIN\GAME::$APP->OBJECTS[$_X][$_Y]) && \MAIN\GAME::$APP->OBJECTS[$_X][$_Y]->id == $this->AGGRED) {

										$DO_ACTION = [$obProp, [$_X, $_Y]];
										$PrevPrior = $this->ACTIONS_PRIOR[$_action][0];
									}
									break;
								case 'near':
									$lastDst = 1000;


									if (is_object($this->AGGRED_TO)) {

										foreach ($nearestCells as $XY) {
											list($_XX, $_YY) = $XY;
											if (!$this->ACTION_TILES[$_XX][$_YY][$obProp->GetActionProvider()]) {
												continue;
											}
											if (!is_object(\MAIN\GAME::$APP->OBJECTS[$_XX][$_YY])) {
												$dst = \Geometry\Geometry::get_distance(\Geometry\Geometry::getCordOBJ($_XX, $_YY), $this->AGGRED_TO);
												if ($dst < $lastDst) {
													$lastDst = $dst;
													$DO_ACTION = [$obProp, [$_XX, $_YY]];
													$PrevPrior = $this->ACTIONS_PRIOR[$_action][0];
												}

											}
										}
									}

//									if (!is_object(\MAIN\GAME::$APP->OBJECTS[$_X][$_Y])) {
//										$dstReserv = \Geometry\Geometry::get_distance(\Geometry\Geometry::getCordOBJ($_X, $_Y), $this->AGGRED_TO);
//										if ($dstReserv < $RESERV_DIST) {
//											$RESERV_DIST = $dst;
//											$RESERV_DO_ACTION = [$obProp, [$_X, $_Y]];
//										}
//									}

									break;

							}


						}

					}
				}
			}

			$this->AGGRED_TO = null;
			if (!is_array($DO_ACTION) || !is_object($DO_ACTION[0]) || !method_exists($DO_ACTION[0], 'DO_')) {
				if (!is_array($RESERV_DO_ACTION) || !is_object($RESERV_DO_ACTION[0]) || !method_exists($RESERV_DO_ACTION[0], 'DO_')) {

					return;
				}
			}

			if (!$justCheck) {
				if (!is_array($DO_ACTION) || !is_object($DO_ACTION[0]) || !method_exists($DO_ACTION[0], 'DO_')) {
					$RESERV_DO_ACTION[0]->DO_(\Geometry\Geometry::getCordOBJ($RESERV_DO_ACTION[1][0], $RESERV_DO_ACTION[1][1]));
				} else {
					$DO_ACTION[0]->DO_(\Geometry\Geometry::getCordOBJ($DO_ACTION[1][0], $DO_ACTION[1][1]));
				}

			}

			return true;
		}

	}

}

trait MoveAI
{

	public function OnNoAI($noPriorAction = [])
	{

		//move To Target
		$NearestCell = [];
		$goTO = null;
		$foundAggred = null;
		for ($agr = 0; $agr < 2 && $foundAggred !== true; $agr++) {
			foreach (\MAIN\GAME::$APP->OBJECTS as $_X => $YY) {
				foreach ($YY as $_Y => $obJ) {
					if ($this->AGGRED && $obJ->id == $this->AGGRED || $foundAggred === false && $obJ->fract !== false && count(array_intersect($obJ->fract, $this->fract)) == 0) {
						$goTO = $obJ;
						break 2;
					}
				}
			}
			$foundAggred = (bool)$goTO;
		}

		if ($foundAggred && is_array($noPriorAction['Move']) && is_array($noPriorAction['Move']['Cords'])) {
			if (method_exists($this, 'OnAI') && !$this->sayedNoAi) {
				$this->sayedNoAi = true;
				$this->OnAI();
			}

			foreach ($noPriorAction['Move']['Cords'] as $X_Y) {
				$dst = \Geometry\Geometry::get_distance(\Geometry\Geometry::getCordOBJ($X_Y[0], $X_Y[1]), $goTO);
				if (!$NearestCell[$dst] || count($NearestCell[$dst]) < count($this->MovementsWays[$X_Y[0]][$X_Y[1]])) {
					$NearestCell[$dst] = $X_Y;
				}

			}

			ksort($NearestCell);
			$X_Y = array_shift($NearestCell);

			$noPriorAction['Move']['Prov']->DO_(\Geometry\Geometry::getCordOBJ($X_Y[0], $X_Y[1]));
		}


	}

}