<?php

function UeDefaultTransform(){
	$v = (object)[
		"Translation" => (object)[ "X" => 0.0, "Y" => 0.0, "Z" => 0.0             ],
		"Rotation"    => (object)[ "X" => 0.0, "Y" => 0.0, "Z" => 0.0, "W" => 1.0 ],
		"Scale3D"     => (object)[ "X" => 1.0, "Y" => 1.0, "Z" => 1.0             ],
	];
	return $v;
}

function UeCompleteTransform(&$export_ref, bool $forceAdd = false){
	$defaultNodeMap = [
		"Translation" => json_decode('{ "$type":"UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI", "StructType":"Vector", "SerializeNone":true, "Name":"RelativeLocation", "Value": [ { "$type":"UAssetAPI.PropertyTypes.Structs.VectorPropertyData, UAssetAPI", "Name":"RelativeLocation", "Value": { "$type":"UAssetAPI.UnrealTypes.FRotator, UAssetAPI", "X":0.0, "Y":0.0, "Z":0.0 } } ] }'),
		
		"Rotation" => json_decode('{ "$type":"UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI", "StructType":"Rotator", "SerializeNone":true, "Name":"RelativeRotation", "Value": [ { "$type":"UAssetAPI.PropertyTypes.Structs.RotatorPropertyData, UAssetAPI", "Name":"RelativeRotation", "Value": { "$type":"UAssetAPI.UnrealTypes.FRotator, UAssetAPI", "Pitch":0.0, "Yaw":0.0, "Roll":0.0 } } ] }'),
		
		"Scale3D" => json_decode('{ "$type":"UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI", "StructType":"Vector", "SerializeNone":true, "Name":"RelativeScale3D", "Value": [ { "$type":"UAssetAPI.PropertyTypes.Structs.VectorPropertyData, UAssetAPI", "Name":"RelativeScale3D", "Value": { "$type":"UAssetAPI.UnrealTypes.FVector, UAssetAPI", "X":1.0, "Y":1.0, "Z":1.0 } } ] }'),
	];
	$jsonKeyToMyKey = array_flip(array_map(function($x) { return $x->Name; }, $defaultNodeMap));
	//var_dump($jsonKeyToMyKey);
	$foundMyKeys = [];
	foreach($export_ref->Data as &$node_ref){
		if(isset($jsonKeyToMyKey[$node_ref->Name])){
			$foundMyKeys[] = $jsonKeyToMyKey[$node_ref->Name];
			$actualValues_ref = &$node_ref->Value[0]->Value;
			//printf("Found %s: %s\n", $node_ref->Name, json_encode($actualValues_ref));
			//var_dump($actualValues_ref);
			unset($actualValues_ref);
		}
	}unset($node_ref);
	
	if((count($foundMyKeys) > 0 && count($defaultNodeMap)) || $forceAdd){
		foreach($defaultNodeMap as $myKey => $defaultNode){
			if(!in_array($myKey, $foundMyKeys)){
				$export_ref->Data[] = $defaultNode;
			}
		}
	}
}

function UeTransformUnpack(mixed $input){
	$obj = UeDefaultTransform();
	$keyNames = array_keys((array)$obj);
	
	static $extraKeyMap = [
		"RelativeLocation" => "Translation",
		"RelativeRotation" => "Rotation",
		"RelativeScale3D"  => "Scale3D",
	];
	
	if(isset($input->Data) && isset($input->ObjectName)){
		$input = $input->Data;
	}
	
	//printf("Trying to unpack %s\n", json_encode($input));
	if(is_object($input)){
		foreach($keyNames as $key){
			if(isset($obj->$key) && isset($input->$key)){
				//var_dump($obj->$key);
				if(isset($obj->$key->X) && isset($input->$key->X)){ $obj->$key->X = (float)$input->$key->X; }
				if(isset($obj->$key->Y) && isset($input->$key->Y)){ $obj->$key->Y = (float)$input->$key->Y; }
				if(isset($obj->$key->Z) && isset($input->$key->Z)){ $obj->$key->Z = (float)$input->$key->Z; }
				if(isset($obj->$key->W) && isset($input->$key->W)){ $obj->$key->W = (float)$input->$key->W; }
				if(isset($obj->$key->Pitch) && isset($input->$key->Pitch)){ $obj->$key->X = (float)$input->$key->Pitch; }
				if(isset($obj->$key->Yaw)   && isset($input->$key->Yaw)  ){ $obj->$key->Y = (float)$input->$key->Yaw;   }
				if(isset($obj->$key->Roll)  && isset($input->$key->Roll) ){ $obj->$key->Z = (float)$input->$key->Roll;  }
			}
		}
		if(isset($input->x)){     $obj->Translation->X = (float)$input->x;     }
		if(isset($input->y)){     $obj->Translation->Y = (float)$input->y;     }
		if(isset($input->z)){     $obj->Translation->Z = (float)$input->z;     }
		if(isset($input->pitch)){ $obj->Rotation->X    = (float)$input->pitch; }
		if(isset($input->yaw)){   $obj->Rotation->Y    = (float)$input->yaw;   }
		if(isset($input->roll)){  $obj->Rotation->Z    = (float)$input->roll;  }
		if(isset($input->sx)){    $obj->Scale3D->X     = (float)$input->sx;    }
		if(isset($input->sy)){    $obj->Scale3D->Y     = (float)$input->sy;    }
		if(isset($input->sz)){    $obj->Scale3D->Z     = (float)$input->sz;    }
		
		if(isset($input->X)){     $obj->Translation->X = (float)$input->X;     }
		if(isset($input->Y)){     $obj->Translation->Y = (float)$input->Y;     }
		if(isset($input->Z)){     $obj->Translation->Z = (float)$input->Z;     }
		if(isset($input->Pitch)){ $obj->Rotation->X    = (float)$input->Pitch; }
		if(isset($input->Yaw)){   $obj->Rotation->Y    = (float)$input->Yaw;   }
		if(isset($input->Roll)){  $obj->Rotation->Z    = (float)$input->Roll;  }
		if(isset($input->SX)){    $obj->Scale3D->X     = (float)$input->SX;    }
		if(isset($input->SY)){    $obj->Scale3D->Y     = (float)$input->SY;    }
		if(isset($input->SZ)){    $obj->Scale3D->Z     = (float)$input->SZ;    }
		
	}elseif(is_array($input)){
		foreach($input as $key => $value){
			//printf("%s -> %s\n\n", $key, json_encode($value));
			if(is_string($key) && in_array($key, $keyNames)){
				if(isset($obj->$key->X) && isset($value->X)){ $obj->$key->X = (float)$value->X; }
				if(isset($obj->$key->Y) && isset($value->Y)){ $obj->$key->Y = (float)$value->Y; }
				if(isset($obj->$key->Z) && isset($value->Z)){ $obj->$key->Z = (float)$value->Z; }
				if(isset($obj->$key->W) && isset($value->W)){ $obj->$key->W = (float)$value->W; }
				//$obj->$key = (float)$value;
			}elseif(is_object($value) && isset($value->Name) && isset($value->Value)){
				if(isset($extraKeyMap[$value->Name])){
					//$jsonKey = $value->Name;
					$myKey   = $extraKeyMap[$value->Name];
					$actualValues = $value->Value[0]->Value;
					//printf("%s -> %s\n\n", $key, json_encode($value));
					if(isset($obj->$myKey->X) && isset($actualValues->X)){ $obj->$myKey->X = (float)$actualValues->X; }
					if(isset($obj->$myKey->Y) && isset($actualValues->Y)){ $obj->$myKey->Y = (float)$actualValues->Y; }
					if(isset($obj->$myKey->Z) && isset($actualValues->Z)){ $obj->$myKey->Z = (float)$actualValues->Z; }
					if(isset($obj->$myKey->W) && isset($actualValues->W)){ $obj->$myKey->W = (float)$actualValues->W; }
					if(isset($obj->$myKey->X) && isset($actualValues->Pitch)){ $obj->$myKey->X = (float)$actualValues->Pitch; }
					if(isset($obj->$myKey->Y) && isset($actualValues->Yaw)  ){ $obj->$myKey->Y = (float)$actualValues->Yaw;   }
					if(isset($obj->$myKey->Z) && isset($actualValues->Roll) ){ $obj->$myKey->Z = (float)$actualValues->Roll;  }
				}
			}
		}
		
	}elseif(is_string($input)){
		// "1.000000,2.000000,3.000000|0.000000,0.000000,0.000000|1.000000,1.000000,1.000000"
		$tmp = explode("|", $input);
		if(count($tmp) != 3){
			printf("%s is not a valid transform\n", $input);
			exit(1);
		}
		foreach($keyNames as $index => $keyName){
			$triplet = explode(",", $tmp[$index]);
			$obj->$keyName->X = (float)$triplet[0];
			$obj->$keyName->Y = (float)$triplet[1];
			$obj->$keyName->Z = (float)$triplet[2];
			//$obj->$keyName->W = (float)1.0;
			// Yeah I'm not writing a quat to rotator conversion.
		}
	}
	return $obj;
}

function UeBoxUnpack(mixed $input){
	$obj = (object)[
		"MinX" => 0.0,
		"MinY" => 0.0,
		"MinZ" => 0.0,
		"MaxX" => 0.0,
		"MaxY" => 0.0,
		"MaxZ" => 0.0,
	];
	$keyNames = array_keys((array)$obj);
	if(is_object($input)){
		if(isset($input->MinX)){ $obj->MinX = (float)$input->MinX; };
		if(isset($input->MinY)){ $obj->MinY = (float)$input->MinY; };
		if(isset($input->MinZ)){ $obj->MinZ = (float)$input->MinZ; };
		if(isset($input->MaxX)){ $obj->MaxX = (float)$input->MaxX; };
		if(isset($input->MaxY)){ $obj->MaxY = (float)$input->MaxY; };
		if(isset($input->MaxZ)){ $obj->MaxZ = (float)$input->MaxZ; };
		
		if(isset($input->{'Min'}->X)){ $obj->MinX = (float)$input->{'Min'}->X; };
		if(isset($input->{'Min'}->Y)){ $obj->MinY = (float)$input->{'Min'}->Y; };
		if(isset($input->{'Min'}->Z)){ $obj->MinZ = (float)$input->{'Min'}->Z; };
		if(isset($input->{'Max'}->X)){ $obj->MaxX = (float)$input->{'Max'}->X; };
		if(isset($input->{'Max'}->Y)){ $obj->MaxY = (float)$input->{'Max'}->Y; };
		if(isset($input->{'Max'}->Z)){ $obj->MaxZ = (float)$input->{'Max'}->Z; };
		
	}elseif(is_array($input)){
		// ?
		
	}elseif(is_string($input)){
		// "Min=X=22269.756 Y=18502.064 Z=14836.938|Max=X=22764.756 Y=19162.064 Z=15316.938"
		$tmp = explode("|", $input);
		if(count($tmp) != 2){
			printf("%s is not a valid box\n", $input);
			exit(1);
		}
		foreach($tmp as $halfString){
			if(!preg_match("/^(Min|Max)=X=((?:\-|\+)?[\d\.]+) Y=((?:\-|\+)?[\d\.]+) Z=((?:\-|\+)?[\d\.]+)$/", $halfString, $matches)){
				printf("%s failed to parse\n", $halfString);
				exit(1);
			}
			if($matches[1] == "Min"){ $obj->MinX = $matches[2]; $obj->MinY = $matches[3]; $obj->MinZ = $matches[4]; }
			else                    { $obj->MaxX = $matches[2]; $obj->MaxY = $matches[3]; $obj->MaxZ = $matches[4]; }
		}
	}
	// todo: check min/max?
	return $obj;
}

function UeTransformPackInto(object $t, mixed &$output){
	if(is_string($output)){
		$output = sprintf("%.6f,%.6f,%.6f|%.6f,%.6f,%.6f|%.6f,%.6f,%.6f",
					$t->Translation->X, $t->Translation->Y, $t->Translation->Z,
					$t->Rotation->X,    $t->Rotation->Y,    $t->Rotation->Z,
					$t->Scale3D->X,     $t->Scale3D->Y,     $t->Scale3D->Z
					// still not writing a quat to rotator conversion.
				);
	
	}elseif(is_array($output)){
		foreach($output as $key => &$subArray_ref){
			foreach($subArray_ref as $subKey => &$subValue_ref){
				//printf("|%s| |%s| |%s|\n", $key, $subKey, $subValue_ref);
				$subValue_ref = (float)$t->$key->$subKey;
				//$subValue_ref = sprintf("%.10f", $t->$key->$subKey);
			}unset($subValue_ref);
		}unset($subArray_ref);
	
	}elseif(is_object($output) && isset($output->X) && isset($output->Y) && isset($output->Z)){
		$output->X = (float)$t->Translation->X;
		$output->Y = (float)$t->Translation->Y;
		$output->Z = (float)$t->Translation->Z;
	
	}elseif(is_object($output) && isset($output->Pitch) && isset($output->Yaw) && isset($output->Roll)){
		$output->Pitch = (float)$t->Rotation->Pitch;
		$output->Yaw   = (float)$t->Rotation->Yaw;
		$output->Roll  = (float)$t->Rotation->Roll;
	
	}elseif(is_object($output) && isset($output->Data)){
		static $extraKeyMap = [
			"RelativeLocation" => "Translation",
			"RelativeRotation" => "Rotation",
			"RelativeScale3D"  => "Scale3D",
		];
		foreach($output->Data as &$node_ref){
			if(isset($node_ref->Name) && isset($extraKeyMap[$node_ref->Name])){
				$myKey = $extraKeyMap[$node_ref->Name];
				$actualValues_ref = &$node_ref->Value[0]->Value;
				//var_dump($actualValues_ref);
				if(isset($t->$myKey->X) && isset($actualValues_ref->X)){ $actualValues_ref->X = (float)$t->$myKey->X; }
				if(isset($t->$myKey->Y) && isset($actualValues_ref->Y)){ $actualValues_ref->Y = (float)$t->$myKey->Y; }
				if(isset($t->$myKey->Z) && isset($actualValues_ref->Z)){ $actualValues_ref->Z = (float)$t->$myKey->Z; }
				if(isset($t->$myKey->W) && isset($actualValues_ref->W)){ $actualValues_ref->W = (float)$t->$myKey->W; }
				if(isset($t->$myKey->X) && isset($actualValues_ref->Pitch)){ $actualValues_ref->Pitch = (float)$t->$myKey->X; }
				if(isset($t->$myKey->Y) && isset($actualValues_ref->Yaw)  ){ $actualValues_ref->Yaw   = (float)$t->$myKey->Y; }
				if(isset($t->$myKey->Z) && isset($actualValues_ref->Roll) ){ $actualValues_ref->Roll  = (float)$t->$myKey->Z; }
				
				unset($actualValues_ref);
			}
		}unset($node_ref);
	
	}else{
		printf("[ERROR] Failed to pack:\n%s\n%s\n", json_encode($t, 0xc0), json_encode($output, 0xc0));
		exit(1);
	}
}

function UeBoxPackInto(object $box, mixed &$output){
	if(is_string($output)){
		//printf("How do I pack |%s|\ninto string |%s|\n\n", json_encode($box), $output);
		$output = sprintf("Min=X=%.3f Y=%.3f Z=%.3f|Max=X=%.3f Y=%.3f Z=%.3f",
						$box->MinX, $box->MinY, $box->MinZ,
						$box->MaxX, $box->MaxY, $box->MaxZ
				);
	//}elseif(is_array($output)){
	}elseif(is_object($output)){
		//printf("How do I pack |%s|\ninto object |%s|\n\n", json_encode($box), json_encode($output));
		
		if(isset($output->MinX)){ $output->MinX = (float)$box->MinX; };
		if(isset($output->MinY)){ $output->MinY = (float)$box->MinY; };
		if(isset($output->MinZ)){ $output->MinZ = (float)$box->MinZ; };
		if(isset($output->MaxX)){ $output->MaxX = (float)$box->MaxX; };
		if(isset($output->MaxY)){ $output->MaxY = (float)$box->MaxY; };
		if(isset($output->MaxZ)){ $output->MaxZ = (float)$box->MaxZ; };
		
		if(isset($output->{'Min'}->X)){ $output->{'Min'}->X = (float)$box->MinX; };
		if(isset($output->{'Min'}->Y)){ $output->{'Min'}->Y = (float)$box->MinY; };
		if(isset($output->{'Min'}->Z)){ $output->{'Min'}->Z = (float)$box->MinZ; };
		if(isset($output->{'Max'}->X)){ $output->{'Max'}->X = (float)$box->MaxX; };
		if(isset($output->{'Max'}->Y)){ $output->{'Max'}->Y = (float)$box->MaxY; };
		if(isset($output->{'Max'}->Z)){ $output->{'Max'}->Z = (float)$box->MaxZ; };
	}
}

function UeTransformAdd($a, $b){
	$result = unserialize(serialize($a));
	$result->Translation->X += $b->Translation->X;
	$result->Translation->Y += $b->Translation->Y;
	$result->Translation->Z += $b->Translation->Z;
	return $result;
}

function UeTransformSub($a, $b){
	$result = unserialize(serialize($a));
	$result->Translation->X -= $b->Translation->X;
	$result->Translation->Y -= $b->Translation->Y;
	$result->Translation->Z -= $b->Translation->Z;
	return $result;
}

function UeBoxAdd($box, $v){
	$result = unserialize(serialize($box));
	$result->MinX += $v->Translation->X;
	$result->MinY += $v->Translation->Y;
	$result->MinZ += $v->Translation->Z;
	$result->MaxX += $v->Translation->X;
	$result->MaxY += $v->Translation->Y;
	$result->MaxZ += $v->Translation->Z;
	return $result;
}

function UeBoxSub($box, $v){
	$result = unserialize(serialize($box));
	$result->MinX -= $v->Translation->X;
	$result->MinY -= $v->Translation->Y;
	$result->MinZ -= $v->Translation->Z;
	$result->MaxX -= $v->Translation->X;
	$result->MaxY -= $v->Translation->Y;
	$result->MaxZ -= $v->Translation->Z;
	return $result;
}

function UeTransformRotateAround($t, $rotator){
	// Does not support rotator scaling yet.
	
	$result = unserialize(serialize($t));
	
	$result = UeTransformSub($result, $rotator); // normalize to local space
	
	$pitchRad = deg2rad($rotator->Rotation->X);
	$yawRad   = deg2rad($rotator->Rotation->Y);
	$rollRad  = deg2rad($rotator->Rotation->Z);
	
	$newX = $result->Translation->X;
	$newY = $result->Translation->Y;
	$newZ = $result->Translation->Z;
	
	$x =  $newX * cos($pitchRad) + $newZ * sin($pitchRad);
	$z = -$newX * sin($pitchRad) + $newZ * cos($pitchRad);
	$newX = $x; // don't assign until BOTH calculations are done
	$newZ = $z; // don't assign until BOTH calculations are done
	
	$x = $newX * cos($yawRad) - $newY * sin($yawRad);
	$y = $newX * sin($yawRad) + $newY * cos($yawRad);
	$newX = $x; // don't assign until BOTH calculations are done
	$newY = $y; // don't assign until BOTH calculations are done
	
	$y = $newY * cos($rollRad) - $newZ * sin($rollRad);
	$z = $newY * sin($rollRad) + $newZ * cos($rollRad);
	$newY = $y; // don't assign until BOTH calculations are done
	$newZ = $z; // don't assign until BOTH calculations are done
	
	$result->Translation->X = $newX;
	$result->Translation->Y = $newY;
	$result->Translation->Z = $newZ;
	$result->Rotation->X += $rotator->Rotation->X;
	$result->Rotation->Y += $rotator->Rotation->Y;
	$result->Rotation->Z += $rotator->Rotation->Z;
	
	$result = UeTransformAdd($result, $rotator); // return to world space
	
	return $result;
}

function UeTransformChildToWorld($parent, $child){
	// Does not support parent scaling yet.
	
	$p = UeTransformUnpack($parent);
	$c = UeTransformUnpack($child);
	$c->Translation->X *= $p->Scale3D->X;
	$c->Translation->Y *= $p->Scale3D->Y;
	$c->Translation->Z *= $p->Scale3D->Z;
	$t = UeTransformAdd($p, $c);
	$t = UeTransformRotateAround($t, $p);
	return $t;
}

function UeBoxRotateAround($box, $rotator){
	$tMin = UeDefaultTransform();
	$tMin->Translation->X = $box->MinX;
	$tMin->Translation->Y = $box->MinY;
	$tMin->Translation->Z = $box->MinY;
	$tMax = UeDefaultTransform();
	$tMax->Translation->X = $box->MaxX;
	$tMax->Translation->Y = $box->MaxY;
	$tMax->Translation->Z = $box->MaxY;
	$tMin = UeTransformRotateAround($tMin, $rotator);
	$tMin = UeTransformRotateAround($tMax, $rotator);
	$result = unserialize(serialize($box));
	$result->MinX = $tMin->Translation->X;
	$result->MinY = $tMin->Translation->Y;
	$result->MinZ = $tMin->Translation->Z;
	$result->MaxX = $tMax->Translation->X;
	$result->MaxY = $tMax->Translation->Y;
	$result->MaxZ = $tMax->Translation->Z;
	return $result;
}

// Old-style format.
function ExtractCoords(string $transform){
	if(!preg_match("/^(-?[0-9\.]+),(-?[0-9\.]+),(-?[0-9\.]+)\|(-?[0-9\.]+),(\-?[0-9\.]+),(-?[0-9\.]+)\|-?[0-9\.]+,-?[0-9\.]+,-?[0-9\.]+[\r\n]*$/", $transform, $matches) || count($matches) < 7){
		printf("Failed to extract coordinates from transform \"%s\"\n", $transform);
		exit(0);
	}
	return (object)[
		"x"     => (float)$matches[1],
		"y"     => (float)$matches[2],
		"z"     => (float)$matches[3],
		"pitch" => (float)$matches[4],
		"yaw"   => (float)$matches[5],
		"roll"  => (float)$matches[6],
		
		"rot"   => (float)$matches[5], // legacy compat
	];
}

// Old-style format.
function ExtractBoxCenter(string $boxString){
	// Sample: "Min=X=-24133.297 Y=103329.375 Z=26508.773|Max=X=-23928.496 Y=103534.172 Z=26713.574"
	if(!preg_match("/^Min=X=(\-?\d+\.?(?:\d+)?) Y=(\-?\d+\.?(?:\d+)?) Z=(\-?\d+\.?(?:\d+)?)\|Max=X=(\-?\d+\.?(?:\d+)?) Y=(\-?\d+\.?(?:\d+)?) Z=(\-?\d+\.?(?:\d+)?)$/", $boxString, $matches) || count($matches) < 7){
		printf("Failed to extract coordinates from box \"%s\"\n", $boxString);
		exit(0);
	}
	return (object)[
		"x" => ((float)$matches[1] + (float)$matches[4]) / 2,
		"y" => ((float)$matches[2] + (float)$matches[5]) / 2,
		"z" => ((float)$matches[3] + (float)$matches[6]) / 2,
		// probably incorrect but I don't care really
		"pitch" => 0,
		"yaw"   => 0,
		"roll"  => 0,
		
		"rot"   => 0, // legacy compat
	];
}

// Old-style format.
function DistanceSquared($a, $b){
	$a = (object)$a;
	$b = (object)$b;
	if(isset($a->Translation) && isset($b->Translation)){
		$dx = $b->Translation->X - $a->Translation->X;
		$dy = $b->Translation->Y - $a->Translation->Y;
		$dz = $b->Translation->Z - $a->Translation->Z;
		return ($dx * $dx + $dy * $dy + $dz * $dz);
	}
	//$dx = $b->x - $a->x;
	//$dy = $b->y - $a->y;
	//$dz = $b->z - $a->z;
	$dx = ($b->x ?? $b->X) - ($a->x ?? $a->X);
	$dy = ($b->y ?? $b->Y) - ($a->y ?? $a->Y);
	$dz = ($b->z ?? $b->Z) - ($a->z ?? $a->Z);
	return ($dx * $dx + $dy * $dy + $dz * $dz);
}

// Old-style format.
function Distance($a, $b){
	return (sqrt(DistanceSquared($a, $b)));
}

// Old-style format.
function Distance2dSquared($a, $b){
	$a = (object)$a;
	$b = (object)$b;
	if(isset($a->Translation) && isset($b->Translation)){
		$dx = $b->Translation->X - $a->Translation->X;
		$dy = $b->Translation->Y - $a->Translation->Y;
		return ($dx * $dx + $dy * $dy);
	}
	//$dx = $b->x - $a->x;
	//$dy = $b->y - $a->y;
	$dx = ($b->x ?? $b->X) - ($a->x ?? $a->X);
	$dy = ($b->y ?? $b->Y) - ($a->y ?? $a->Y);
	return ($dx * $dx + $dy * $dy);
}

// Old-style format.
function Distance2d($a, $b){
	return (sqrt(Distance2dSquared($a, $b)));
}

// Old-style format.
function CombineLocalTransform(object $origin, array $transforms){
	$result = [];
	foreach($transforms as $transform){
		$localpos = ExtractCoords($transform);
		//$result[] = (object)[
		//	"x" => $origin->x + $localpos->x,
		//	"y" => $origin->y + $localpos->y,
		//	"z" => $origin->z + $localpos->z,
		//	"pitch" => $origin->pitch + $localpos->pitch,
		//	"yaw"   => $origin->yaw   + $localpos->yaw,
		//	"roll"  => $origin->roll  + $localpos->roll,
		//	
		//	"rot"   => $origin->rot   + $localpos->rot, // legacy compat
		//];
		// UPD: hey look, fancy calculations with matrices!
		
		$newX = $localpos->x;
		$newY = $localpos->y;
		$newZ = $localpos->z;
		
		$pitchRad = deg2rad($origin->pitch);
		$x =  $newX * cos($pitchRad) + $newZ * sin($pitchRad);
		$z = -$newX * sin($pitchRad) + $newZ * cos($pitchRad);
		$newX = $x; // don't assign until BOTH calculations are done
		$newZ = $z; // don't assign until BOTH calculations are done
		
		$yawRad = deg2rad($origin->yaw);
		$x = $newX * cos($yawRad) - $newY * sin($yawRad);
		$y = $newX * sin($yawRad) + $newY * cos($yawRad);
		$newX = $x; // don't assign until BOTH calculations are done
		$newY = $y; // don't assign until BOTH calculations are done
		
		$rollRad = deg2rad($origin->roll);
		$y = $newY * cos($rollRad) - $newZ * sin($rollRad);
		$z = $newY * sin($rollRad) + $newZ * cos($rollRad);
		$newY = $y; // don't assign until BOTH calculations are done
		$newZ = $z; // don't assign until BOTH calculations are done
		
		$result[] = (object)[
			"x" => $origin->x + $newX,
			"y" => $origin->y + $newY,
			"z" => $origin->z + $newZ,
			"pitch" => $origin->pitch + $localpos->pitch,
			"yaw"   => $origin->yaw   + $localpos->yaw,
			"roll"  => $origin->roll  + $localpos->roll,
			
			"rot"   => $origin->rot   + $localpos->rot, // legacy compat
		];
	}
	return $result;
}

function ParseCoordinates($pid, $ptype, $data){
	$actorCoords = ExtractCoords($data->ActorTransform);
	$coords = [];
	switch($ptype){
		case "followTheShiny":{
			// Seems much more consistent? Large disrepancy with ActorTransform sometimes.
			$shiny = ExtractCoords($data->shinyMeshTransform);
			//if(abs($shiny->x - $actorCoords->x) + abs($shiny->y - $actorCoords->y) > 1500){
			//	printf("Inconsistent coordinates for %d %s: \"%s\" vs \"%s\"\n", $pid, $ptype, $data->ActorTransform, $data->shinyMeshTransform);
			//}
			$coords = [ $shiny ];
			break;
		}
		case "lightPattern":{
			// Matches ActorTransform almost perfectly but let's be safe.
			$capture = ExtractCoords($data->captureComponentTransform);
			//if(abs($capture->x - $actorCoords->x) + abs($capture->y - $actorCoords->y) > 0){
			//	printf("Inconsistent coordinates for %d %s: \"%s\" vs \"%s\"\n", $pid, $ptype, $data->ActorTransform, $data->captureComponentTransform);
			//}
			$coords = [ $capture ];
			break;
		}
		case "matchbox":{
			// Get actual coordinates of both boxes, ignore ActorTransform.
			// OUTDATED as of July 9th
			//$coords = [ ExtractCoords($data->Mesh1Transform), ExtractCoords($data->Mesh2Transform) ];
			
			// OUTDATED since the implementation of Offline Restored Mod.
			$coords = [
				ExtractBoxCenter($data->{"SERIALIZEDSUBCOMP_PuzzleBounds-0"}->Box),
				ExtractBoxCenter($data->{"SERIALIZEDSUBCOMP_PuzzleBounds-1"}->Box),
			];
			
			//$coords = CombineLocalTransform($actorCoords, [ $data->Mesh1Transform, $data->Mesh2Transform ]);
			$a = UeTransformChildToWorld($actorCoords, $data->Mesh1Transform);
			$b = UeTransformChildToWorld($actorCoords, $data->Mesh2Transform);
			$coords = [
				(object)[
					"x"     => $a->Translation->X,
					"y"     => $a->Translation->Y,
					"z"     => $a->Translation->Z,
					"pitch" => $a->Rotation->X,
					"yaw"   => $a->Rotation->X,
					"roll"  => $a->Rotation->X,
				],
				(object)[
					"x"     => $b->Translation->X,
					"y"     => $b->Translation->Y,
					"z"     => $b->Translation->Z,
					"pitch" => $b->Rotation->X,
					"yaw"   => $b->Rotation->X,
					"roll"  => $b->Rotation->X,
				],
			];
			break;
		}
		case "racingBallCourse":{
			$orbs = array_values((array)$data->{"DuplicatedObjectOfType-RacingBallsMeshComponent"});
			sort($orbs, SORT_NUMERIC); // meh
			$coords = CombineLocalTransform($actorCoords, $orbs);
			//var_dump($coords);
			break;
		}
		case "racingRingCourse":{
			$platform = ExtractCoords($data->StartingPlatformTransform);
			$rings = array_values((array)$data->{"DuplicatedObjectOfType-RacingRingsMeshComponent"});
			//sort($rings, SORT_NUMERIC); // meh
			//$coords = CombineLocalTransform($actorCoords, $rings);
			//$coords = [ $platform ];
			$coords = array_values(array_merge([ $platform ], CombineLocalTransform($actorCoords, $rings)));
			//var_dump($coords);
			break;
		}
		case "seek5":{
			$coords = CombineLocalTransform($actorCoords, array_merge([ $data->CentralPillarTransform ], array_values((array)$data->{"DuplicatedObjectOfType-Seek5HiddenObject"})));
			break;
		}
		case "viewfinder":{
			//$coords = [ ExtractCoords($data->ActorTransform), ExtractCoords($data->CameraTransform) ];
			//$cam    = ExtractCoords($data->CameraTransform);
			//$actor  = ExtractCoords($data->ActorTransform);
			//$spawn  = ExtractCoords($data->SpawnTransform);
			//$bounds = ExtractCoords($data->{"SERIALIZEDSUBCOMP_PuzzleBounds-0"}->WorldTransform);
			//$coords = [ $cam, $actor, $spawn, $bounds ];
			// They're all almost the same unfortunately. Viewfinders have special logic; 
			// the pickup spawns in a randomized available location (sometimes very far off).
			// The only exception seems to be Archipelago of Curiosities where you can directly
			// tell where each given viewfinder was taken taken from. For everything else... nope.
			$coords = [ ExtractCoords($data->CameraTransform) ];
			break;
		}
		case "monolithFragment":{
			// this currently will not work from here :(
			break;
		}
		default:{
			$coords = [ ExtractCoords($data->ActorTransform) ];
			break;
		}
	}
	return $coords;
}

function IsValidTriangle(array $triangle){
	if(empty($triangle) || count($triangle) != 3){
		printf("%s: given a triangle with %d points: %s\n", __FUNCTION__, count($triangle), json_encode($triangle));
		return false;
	}
	for($i = 0; $i < 3; ++$i){
		if(!isset($triangle[$i])){
		   printf("%s: malformed triangle data, missing index %d: %s\n", __FUNCTION__, $i, json_encode($triangle));
		   return false;
		}
		$point = (object)$triangle[$i];
		if(!isset($point->x) ||
		   !isset($point->y) ||
		   !is_numeric($point->x) ||
		   !is_numeric($point->y)){
			   printf("%s: malformed triangle data, missing x y values: %s\n", __FUNCTION__, json_encode($triangle));
			   return false;
		}
	}
	return true;
}

function FormPoint($x, $y){
	return (object)[ "x" => $x, "y" => $y ];
}
	
	
function FormVector($a, $b){
	$a = (object)$a;
	$b = (object)$b;
	//return (object)[ "x" => $b->x - $a->x, "y" => $b->y - $a->y ];
	return FormPoint($b->x - $a->x, $b->y - $a->y);
}

function DotProduct($a, $b){
	return ($a->x * $b->x + $a->y * $b->y);
}

function TriangleArea(array $triangle){
	if(!IsValidTriangle($triangle)){
		exit(1);
	}
	$a = (object)$triangle[0];
	$b = (object)$triangle[1];
	$c = (object)$triangle[2];
	$ab = FormVector($a, $b);
	$ac = FormVector($a, $c);
	$crossProduct = $ab->x * $ac->y - $ab->y * $ac->x;
	return (abs($crossProduct) / 2);
}

function ProjectPointOntoLine($point, $a, $b){
	return (TriangleArea([(object)$point, (object)$a, (object)$b]) * 2 / Distance2d($a, $b));
}

function IsLineIntersectingCircle($a, $b, $circle){
	$projectionDist = ProjectPointOntoLine($circle, $a, $b);
	return ($projectionDist <= $circle->radius);
}

function IsLineSegmentIntersectingCircle($a, $b, $circle){
	
	$acDist = Distance2d($a, $circle);
	$bcDist = Distance2d($b, $circle);
	$minDist = min($acDist, $bcDist);
	$maxDist = max($acDist, $bcDist);
	
	$ca = FormVector($circle, $a);
	$cb = FormVector($circle, $b);
	$ab = FormVector($a, $b);
	$ba = FormVector($b, $a);
	
    if(DotProduct($ca, $ba) > 0 && DotProduct($cb, $ab) > 0){
        $minDist = ProjectPointOntoLine($circle, $a, $b);
	}
	$result = ($minDist <= $circle->radius && $maxDist >= $circle->radius);
	
	return $result;
}

function IsPointInCircle($x, $y, $circle){
	return (Distance2dSquared(FormPoint($x, $y), $circle) <= ($circle->radius * $circle->radius));
}

