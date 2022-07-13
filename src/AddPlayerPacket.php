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

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use Ramsey\Uuid\UuidInterface;
use function count;

class AddPlayerPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_PLAYER_PACKET;

	public UuidInterface $uuid;
	public string $username;
	public int $actorRuntimeId;
	public string $platformChatId = "";
	public Vector3 $position;
	public ?Vector3 $motion = null;
	public float $pitch = 0.0;
	public float $yaw = 0.0;
	public float $headYaw = 0.0;
	public ItemStackWrapper $item;
	public int $gameMode;
	/**
	 * @var MetadataProperty[]
	 * @phpstan-var array<int, MetadataProperty>
	 */
	public array $metadata = [];

	public AdventureSettingsPacket|UpdateAbilitiesPacket $packet;

	/** @var EntityLink[] */
	public array $links = [];
	public string $deviceId = ""; //TODO: fill player's device ID (???)
	public int $buildPlatform = DeviceOS::UNKNOWN;

	/**
	 * @generate-create-func
	 * @param MetadataProperty[] $metadata
	 * @param EntityLink[]       $links
	 * @phpstan-param array<int, MetadataProperty> $metadata
	 */
	public static function create(
		UuidInterface $uuid,
		string $username,
		int $actorRuntimeId,
		string $platformChatId,
		Vector3 $position,
		?Vector3 $motion,
		float $pitch,
		float $yaw,
		float $headYaw,
		ItemStackWrapper $item,
		int $gameMode,
		array $metadata,
		AdventureSettingsPacket|UpdateAbilitiesPacket $packet,
		array $links,
		string $deviceId,
		int $buildPlatform,
	) : self{
		$result = new self;
		$result->uuid = $uuid;
		$result->username = $username;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->platformChatId = $platformChatId;
		$result->position = $position;
		$result->motion = $motion;
		$result->pitch = $pitch;
		$result->yaw = $yaw;
		$result->headYaw = $headYaw;
		$result->item = $item;
		$result->gameMode = $gameMode;
		$result->metadata = $metadata;
		$result->packet = $packet;
		$result->links = $links;
		$result->deviceId = $deviceId;
		$result->buildPlatform = $buildPlatform;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->uuid = $in->getUUID();
		$this->username = $in->getString();
		if($in->getProtocolId() < ProtocolInfo::PROTOCOL_1_19_10){
			$in->getActorUniqueId();
		}
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->platformChatId = $in->getString();
		$this->position = $in->getVector3();
		$this->motion = $in->getVector3();
		$this->pitch = $in->getLFloat();
		$this->yaw = $in->getLFloat();
		$this->headYaw = $in->getLFloat();
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_220){
			$this->item = ItemStackWrapper::read($in);
		}else{
			$this->item = ItemStackWrapper::legacy($in->getItemStackWithoutStackId());
		}
		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_18_30){
			$this->gameMode = $in->getVarInt();
		}
		$this->metadata = $in->getEntityMetadata();

		if($in->getProtocolId() >= ProtocolInfo::PROTOCOL_1_19_10){
			$this->packet = new UpdateAbilitiesPacket();
		}else{
			$this->packet = new AdventureSettingsPacket();
		}
		$this->packet->decodePayload($in);

		$linkCount = $in->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[$i] = $in->getEntityLink();
		}

		$this->deviceId = $in->getString();
		$this->buildPlatform = $in->getLInt();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUUID($this->uuid);
		$out->putString($this->username);
		if($out->getProtocolId() < ProtocolInfo::PROTOCOL_1_19_10){
			$out->putActorUniqueId($this->actorRuntimeId);
		}
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putString($this->platformChatId);
		$out->putVector3($this->position);
		$out->putVector3Nullable($this->motion);
		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);
		$out->putLFloat($this->headYaw);
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_16_220){
			$this->item->write($out);
		}else{
			$out->putItemStackWithoutStackId($this->item->getItemStack());
		}
		if($out->getProtocolId() >= ProtocolInfo::PROTOCOL_1_18_30){
			$out->putVarInt($this->gameMode);
		}
		$out->putEntityMetadata($this->metadata);

		$this->packet->encodePayload($out);

		$out->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$out->putEntityLink($link);
		}

		$out->putString($this->deviceId);
		$out->putLInt($this->buildPlatform);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleAddPlayer($this);
	}
}
