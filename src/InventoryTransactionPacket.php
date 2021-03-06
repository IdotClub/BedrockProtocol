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

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use function count;

/**
 * This packet effectively crams multiple packets into one.
 */
class InventoryTransactionPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_TRANSACTION_PACKET;

	public const TYPE_NORMAL = 0;
	public const TYPE_MISMATCH = 1;
	public const TYPE_USE_ITEM = 2;
	public const TYPE_USE_ITEM_ON_ENTITY = 3;
	public const TYPE_RELEASE_ITEM = 4;

	public int $requestId;
	/** @var InventoryTransactionChangedSlotsHack[] */
	public array $requestChangedSlots;
	public TransactionData $trData;
	public bool $hasItemStackIds = true;

	/**
	 * @generate-create-func
	 * @param InventoryTransactionChangedSlotsHack[] $requestChangedSlots
	 */
	public static function create(int $requestId, array $requestChangedSlots, TransactionData $trData, bool $hasItemStackIds) : self{
		$result = new self;
		$result->requestId = $requestId;
		$result->requestChangedSlots = $requestChangedSlots;
		$result->trData = $trData;
		$result->hasItemStackIds = $hasItemStackIds;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->requestId = $in->readGenericTypeNetworkId();
		$this->requestChangedSlots = [];
		if($this->requestId !== 0){
			for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
				$this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}

		$transactionType = $in->getUnsignedVarInt();

		if($in->getProtocolId() < ProtocolInfo::PROTOCOL_1_16_220){
			$this->hasItemStackIds = $in->getBool();
		}

		switch($transactionType){
			case NormalTransactionData::ID:
				$this->trData = new NormalTransactionData();
				break;
			case MismatchTransactionData::ID:
				$this->trData = new MismatchTransactionData();
				break;
			case UseItemTransactionData::ID:
				$this->trData = new UseItemTransactionData();
				break;
			case UseItemOnEntityTransactionData::ID:
				$this->trData = new UseItemOnEntityTransactionData();
				break;
			case ReleaseItemTransactionData::ID:
				$this->trData = new ReleaseItemTransactionData();
				break;
			default:
				throw new PacketDecodeException("Unknown transaction type $transactionType");
		}

		$this->trData->decode($in, $this->hasItemStackIds);
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->writeGenericTypeNetworkId($this->requestId);
		if($this->requestId !== 0){
			$out->putUnsignedVarInt(count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlots){
				$changedSlots->write($out);
			}
		}

		$out->putUnsignedVarInt($this->trData->getTypeId());

		if($out->getProtocolId() < ProtocolInfo::PROTOCOL_1_16_220){
			$out->putBool($this->hasItemStackIds);
		}

		$this->trData->encode($out, $this->hasItemStackIds);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleInventoryTransaction($this);
	}
}
