<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\serializer;

use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;

final class ItemTypeDictionary{

	/**
	 * @var ItemTypeEntry[]
	 * @phpstan-var list<ItemTypeEntry>
	 */
	private array $itemTypes;
	/**
	 * @var string[]
	 * @phpstan-var array<int, string>
	 */
	private array $intToStringIdMap = [];
	/**
	 * @var int[]
	 * @phpstan-var array<string, int>
	 */
	private array $stringToIntMap = [];

	/**
	 * @param ItemTypeEntry[] $itemTypes
	 */
	public function __construct(array $itemTypes){
		$this->itemTypes = $itemTypes;
		foreach($this->itemTypes as $type){
			$this->stringToIntMap[$type->getStringId()] = $type->getNumericId();
			$this->intToStringIdMap[$type->getNumericId()] = $type->getStringId();
		}
	}

	/**
	 * @return ItemTypeEntry[]
	 * @phpstan-return list<ItemTypeEntry>
	 */
	public function getEntries() : array{
		return $this->itemTypes;
	}

	public function fromStringId(string $stringId) : int{
		return $this->stringToIntMap[$stringId] ?? throw new \InvalidArgumentException("Unmapped string ID \"$stringId\"");;
	}

	public function fromIntId(int $intId) : string{
		return $this->intToStringIdMap[$intId] ?? throw new \InvalidArgumentException("Unmapped int ID $intId");;
	}
}
