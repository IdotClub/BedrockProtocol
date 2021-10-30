<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class UseItemOnEntityTransactionData extends TransactionData{
	public const ACTION_INTERACT = 0;
	public const ACTION_ATTACK = 1;
	public const ACTION_ITEM_INTERACT = 2;

	private int $actorRuntimeId;
	private int $actionType;
	private int $hotbarSlot;
	private ItemStackWrapper $itemInHand;
	private Vector3 $playerPosition;
	private Vector3 $clickPosition;

	public function getActorRuntimeId() : int{
		return $this->actorRuntimeId;
	}

	public function getActionType() : int{
		return $this->actionType;
	}

	public function getHotbarSlot() : int{
		return $this->hotbarSlot;
	}

	public function getItemInHand() : ItemStackWrapper{
		return $this->itemInHand;
	}

	public function getPlayerPosition() : Vector3{
		return $this->playerPosition;
	}

	public function getClickPosition() : Vector3{
		return $this->clickPosition;
	}

	public function getTypeId() : int{
		return InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY;
	}

	protected function decodeData(PacketSerializer $stream) : void{
		$this->actorRuntimeId = $stream->getActorRuntimeId();
		$this->actionType = $stream->getUnsignedVarInt();
		$this->hotbarSlot = $stream->getVarInt();
		if($stream->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_220){
			$this->itemInHand = ItemStackWrapper::read($stream);
		}else{
			$this->itemInHand = ItemStackWrapper::legacy($stream->getItemStackWithoutStackId());
		}
		$this->playerPosition = $stream->getVector3();
		$this->clickPosition = $stream->getVector3();
	}

	protected function encodeData(PacketSerializer $stream) : void{
		$stream->putActorRuntimeId($this->actorRuntimeId);
		$stream->putUnsignedVarInt($this->actionType);
		$stream->putVarInt($this->hotbarSlot);
		if($stream->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_220){
			$this->itemInHand->write($stream);
		}else{
			$stream->putItemStackWithoutStackId($this->itemInHand->getItemStack());
		}
		$stream->putVector3($this->playerPosition);
		$stream->putVector3($this->clickPosition);
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function new(array $actions, int $actorRuntimeId, int $actionType, int $hotbarSlot, ItemStackWrapper $itemInHand, Vector3 $playerPosition, Vector3 $clickPosition) : self{
		$result = new self;
		$result->actions = $actions;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->actionType = $actionType;
		$result->hotbarSlot = $hotbarSlot;
		$result->itemInHand = $itemInHand;
		$result->playerPosition = $playerPosition;
		$result->clickPosition = $clickPosition;
		return $result;
	}
}
