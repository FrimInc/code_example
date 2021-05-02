<?php

namespace Objects;

use MECH\CraftTable;

class Base
{
	var $X = 0;
	var $Y = 0;
	var $Name = 'something';
	var $takable = false;
	var $openable = false;

	var $SEX = 'M';

	var $transparent = true;
	var $ACTION_CODE = '';
	var $ACTION_NAME = '';

	var $PROPS = [];
	var $PROPS_VALS = [];
	var $PROPS_STR = [];

	var $fract = false;

	var $SKILLS = [];
	var $SKILLS_VALS = [];
	var $SKILLS_STR = [];

	var $craft_id = null;

	var $BAG = [];
	var $BAG_VALS = [];
	var $BAG_STR = [];

	var $TOUCHES = [];
	var $SUIT_TO = [];
	var $USE_ACTIONS = [];

	public function GetBaseVars()
	{
		return [];
	}

	public function Drop($arLut)
	{
		$lut = new Lut();
		$lut->ITEMS = $arLut;
		\MAIN\GAME::$APP->OBJECTS[$this->X][$this->Y] = $lut;

	}

	public function DropAgro()
	{

		foreach (\MAIN\GAME::$APP->OBJECTS as $_X => $YY) {
			foreach ($YY as $_Y => $obJ) {
				if (is_object($obJ) && $obJ->AGGRED == $this->id) {
					$obJ->AGGRED = null;
				}
			}
		}

	}

	public function setProp($SkillName_, $From = null)
	{
		if (class_exists($SkillName = '\Props\\' . $SkillName_)) {
			$newSkill = new $SkillName($this);
			$this->PROPS[] = $newSkill;
			$this->PROPS_STR[] = $SkillName_;
			if (is_object($From)) {
				$From->Act('наделяет ' . $this->GetName() . ' способностью S::' . $newSkill->GetName());
			}

		}

	}

	public function setSkill($SkillName_, $From = null)
	{
		if (class_exists($SkillName = '\SKILLS\\' . $SkillName_)) {
			$newSkill = new $SkillName($this);
			$this->SKILLS[] = $newSkill;
			$this->SKILLS_STR[] = $SkillName_;
			if (is_object($From)) {
				$From->Act('наделяет ' . $this->GetName() . ' способностью S::' . $newSkill->GetName());
			}
		}

	}

	public function getSkill($SkillName)
	{
		foreach ($this->SKILLS as $obSkill) {
			if ($obSkill->getName() == $SkillName) {
				return $obSkill;
			}
		}

	}

	public function ppSkill($SkillName_, $ExpPP = 0)
	{
		foreach ($this->SKILLS as $obSkill) {
			if (is_a($obSkill, $SkillName = '\SKILLS\\' . $SkillName_)) {
				$obSkill->GetExp($ExpPP);
				$obSkill->FixExp();
				return $obSkill;
			}
		}

	}

	public function RemoveSkill($SkillName_)
	{
		$SkillName = '\SKILLS\\' . $SkillName_;
		foreach ($this->SKILLS as $_ => $obSkill) {
			if (is_a($obSkill, $SkillName)) {
				$this->Act('теряет способность S::' . $obSkill->GetName());
				unset($this->SKILLS[$_]);
				$this->SKILLS_STR = array_diff($this->SKILLS_STR, [$SkillName_]);
			}
		}

	}

	public function GenerateViews($X, $Y, $PrevCords = [])
	{

		if ($this->area <= count($PrevCords)) {
			return [];
		}

		for ($_x = -1; $_x <= 1; $_x++) {
			for ($_y = -1; $_y <= 1; $_y++) {

				if ($X % 2 && ($_y == -1 && $_x == -1 || $_y == -1 && $_x == 1)) {
					continue;
				}

				if ((!($X % 2)) && ($_y == 1 && $_x == -1 || $_y == 1 && $_x == 1)) {
					continue;
				}

				$XX = $X + $_x;
				$YY = $Y + $_y;
				if ($XX < 0 || $YY < 0) {
					continue;
				}
				$cords = $PrevCords;

				if (!$this->ViewVays[$XX][$YY] || (count($this->ViewVays[$XX][$YY]) > count($cords)) ||
					(count($this->ViewVays[$XX][$YY]) == count($PrevCords) && \Geometry\Geometry::GetMovementLinear($this->ViewVays[$XX][$YY]) > \Geometry\Geometry::GetMovementLinear($PrevCords))
				) {
					$this->ViewVays[$XX][$YY] = $PrevCords;
					if ((\MAIN\GAME::$APP->OBJECTS[$XX][$YY] && \MAIN\GAME::$APP->OBJECTS[$XX][$YY]->transparent) || !\MAIN\GAME::$APP->OBJECTS[$XX][$YY] && \MAIN\GAME::$APP->MAP[$XX][$YY]->transparent) {
						$cords[] = [$XX, $YY];

						$this->GenerateViews($XX, $YY, $cords);
					}


				}
			}
		}

		return $this->ViewVays;
	}

	public function GenerateMoves($X, $Y, $PrevCords = [])
	{
		$this->XXYY = [];
		if ($this->area <= count($PrevCords)) {
			return;
		}

		for ($_x = -1; $_x <= 1; $_x++) {
			for ($_y = -1; $_y <= 1; $_y++) {

				if ($X % 2 && ($_y == -1 && $_x == -1 || $_y == -1 && $_x == 1)) {
					continue;
				}

				if (!($X % 2) && ($_y == 1 && $_x == -1 || $_y == 1 && $_x == 1)) {
					continue;
				}

				$XX = $X + $_x;
				$YY = $Y + $_y;
				if ($XX < 0 || $YY < 0) {
					continue;
				}
				$cords = $PrevCords;

				if (!$this->ViewLineVays[$XX][$YY] || count($this->ViewLineVays[$XX][$YY]) > count($cords)
					||
					($this->ViewLineVays[$XX][$YY] && \Geometry\Geometry::GetMovementLinear($this->ViewLineVays[$XX][$YY]) > \Geometry\Geometry::GetMovementLinear($cords))
				) {

					$this->ViewLineVays[$XX][$YY] = $cords;
					$cords[] = [$XX, $YY];
					$this->GenerateMoves($XX, $YY, $cords);
				}

			}
		}
	}

	public function __construct($vars = [])
	{
		$this->ViewVays = [];
		$this->ViewLineVays = [];
		if (is_array($vars)) {
			foreach ($vars as $varName => $varValue) {
				$this->{$varName} = $varValue;
			}
		}

		if (!$this->query && \MAIN\GAME::$APP) {
			\MAIN\GAME::$APP->LAST_ACTOR++;
			$this->query = \MAIN\GAME::$APP->LAST_ACTOR;
		}

		$R = $this->GetParentSkillsAndProps();

		$this->PROPS_STR = $R['PROPS_STR'];
		$this->SKILLS_STR = $R['SKILLS_STR'];
		foreach ($this->PROPS_STR as $prop_name) {
			if (class_exists($PropClass = '\PROPS\\' . $prop_name)) {
				$this->PROPS[] = new $PropClass($this);
			}
		}

		foreach ($this->SKILLS_STR as $prop_name) {
			if (class_exists($PropClass = '\SKILLS\\' . $prop_name)) {
				$this->SKILLS[$prop_name] = new $PropClass($this);
			}
		}

		foreach ((array)$this->BAG_STR as $_ID => $bag_name) {
			if (class_exists($PropClass = $bag_name)) {
				$this->BAG[$_ID] = new $PropClass($this->BAG_VALS[$_ID], $this);
			}
		}

		if (!$this->id) {
			$this->id = time() . '_' . rand(11111, 99999);
		}
	}

	public function GetParentSkillsAndProps()
	{
		$ret = ['SKILLS_STR' => [], 'PROPS_STR' => []];
		if ($parentClass = get_parent_class($this)) {
			$parentClass = new $parentClass();
			$PR = $parentClass->GetParentSkillsAndProps();
			$ret['SKILLS_STR'] = $PR['SKILLS_STR'];
			$ret['PROPS_STR'] = $PR['PROPS_STR'];
		}
		$ret['SKILLS_STR'] = array_unique(array_filter(array_merge($this->SKILLS_STR, $ret['SKILLS_STR'])));
		$ret['PROPS_STR'] = array_unique(array_filter(array_merge($this->PROPS_STR, $ret['PROPS_STR'])));
		return $ret;
	}

	public function GetName($replace = false)
	{
		return $this->Name ? ($replace ? str_replace('_', ' ', $this->Name) : str_replace(' ', '_', $this->Name)) : $this->id;
	}

	public function OnMakeCraft($arParams)
	{

		return CraftTable::Check($arParams, $this->ME);
	}

	public function __sleep()
	{
		$this->ACTION_TILES = [[]];
		$this->MovementsWays = [[]];
		if (is_object($this->ME)) {
			$this->ME->ACTION_TILES = [[]];
			$this->ME->MovementsWays = [[]];
		}
		$this->PROPS = [];
		$this->SKILLS = [];

		foreach ($this->BAG as $bagItem) {
			$this->BAG_VALS[$bagItem->id] = $bagItem->GetValues();
			$this->BAG_STR[$bagItem->id] = get_class($bagItem);
		}

		$this->BAG = [];

	}

	public function GetDamage($Damage, $Actor, $with = null)
	{
		$Damaged = false;

		foreach ($this->PROPS as $obProp) {
			if (is_a($obProp, '\PROPS\Damageble')) {
				$Damaged = true;
				$obProp->TakeDamage($Damage, $Actor, $with);
			}
		}

		if (!$Damaged) {
			return 'O::' . $this->GetName() . ' не получает никакого урона.';
		}
		return true;

	}

	public function Say($message)
	{
		LC('MM:' . $this->GetName() . ':' . $message);
	}

	public function Act($message)
	{
		LC('MM::O::' . $this->GetName() . ' ' . $message);
	}

	public function GetActionProvider()
	{
		return $this->ACTION_CODE;
	}

	public function GetActionName()
	{
		return $this->ACTION_NAME;
	}

	public function GetValues()
	{
		$this->ME = null;
		$this->SKILLS = [];
		$this->__sleep();
		return get_object_vars($this);
	}

	public function GetParams($addSlot = null)
	{
		$SUIT_TO = [];
		$RealActions = [];
		if (is_object($this->ME) && is_object($this->ME->SUIT)) {
			$SUIT_TO = array_intersect($this->SUIT_TO, array_keys($this->ME->SUIT->EMPTY_SLOTS));

			foreach ($SUIT_TO as $_ => $slotName) {
				if ($this->SUIT_SLOTS > $this->ME->SUIT->EMPTY_SLOTS[$slotName]) {
					unset($SUIT_TO[$_]);
				}
			}


		}

		if ($addSlot != 'Рюкзак') {
			$SUIT_TO[] = 'Крафт';
		} else {
			foreach ($this->ME->SUIT->SLOTS['Крафт'] as $obCraft) {
				if (is_object($obCraft) && $obCraft->id == $this->id) {
					$RealActions = ['MakeCraft' => 'СоздатьНечто'];
				}
			}

		}

		if ($addSlot) {
			$SUIT_TO[] = $addSlot;
		}

		return array_merge(['img' => $this->img, 'SUIT_TO' => $SUIT_TO, 'slots' => $this->SUIT_SLOTS, 'USE_ACTIONS' => is_object($this->ME) ? array_merge($this->USE_ACTIONS, $RealActions) : []], $this->GetInfo());
	}

	public function DropFromBAG($itemID)
	{

		$this->BAG[$itemID] = null;
		$this->BAG = array_filter($this->BAG);

		$this->BAG_STR[$itemID] = null;
		$this->BAG_STR = array_filter($this->BAG_STR);

		$this->BAG_VALS[$itemID] = null;
		$this->BAG_VALS = array_filter($this->BAG_VALS);
	}

	public function GetInfoStats($My, $Their, $viewer)
	{

	}

	public function GetInfo()
	{
		return [
			'Id'     => $this->id,
			'Name'   => $this->GetName(true),
			'Cost'   => $this->cost,
			'Weight' => $this->weight
		];
	}

	public function AggrFract($objTO)
	{
		if (!is_array($this->fract)) {
			return;
		}
		foreach (\MAIN\GAME::$APP->OBJECTS as $_X => $YY) {
			foreach ($YY as $_Y => $obJ) {
				if (!is_array($obJ->fract)) {
					continue;
				}

				if (count(array_intersect($this->fract, $obJ->fract))) {
					$this->AGGRED = $objTO->id;
				}
				if (method_exists($obJ, 'OnAggrFract')) {
					$obJ->OnAggrFract();
				}
			}
		}
	}
}