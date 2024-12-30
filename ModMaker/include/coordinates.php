<?php

function UeTransformUnpack(mixed $input){
	$obj = (object)[
		"Translation" => (object)[ "X" => 0.0, "Y" => 0.0, "Z" => 0.0             ],
		"Rotation"    => (object)[ "X" => 0.0, "Y" => 0.0, "Z" => 0.0, "W" => 1.0 ],
		"Scale3D"     => (object)[ "X" => 1.0, "Y" => 1.0, "Z" => 1.0             ],
	];
	$keyNames = array_keys((array)$obj);
	
	//printf("Trying to unpack %s\n", json_encode($input));
	if(is_object($input)){
		foreach($keyNames as $key){
			if(isset($obj->$key) && isset($input->$key)){
				if(isset($obj->$key->X) && isset($input->$key->X)){ $obj->$key->X = (float)$input->$key->X; }
				if(isset($obj->$key->Y) && isset($input->$key->Y)){ $obj->$key->Y = (float)$input->$key->Y; }
				if(isset($obj->$key->Z) && isset($input->$key->Z)){ $obj->$key->Z = (float)$input->$key->Z; }
				if(isset($obj->$key->W) && isset($input->$key->W)){ $obj->$key->W = (float)$input->$key->W; }
			}
		}
		if(isset($input->x)){     $obj->Translation->X = (float)$input->x;     }
		if(isset($input->y)){     $obj->Translation->Y = (float)$input->y;     }
		if(isset($input->z)){     $obj->Translation->Z = (float)$input->z;     }
		if(isset($input->pitch)){ $obj->Rotation->X    = (float)$input->pitch; }
		if(isset($input->yaw)){   $obj->Rotation->Y    = (float)$input->yaw;   }
		if(isset($input->roll)){  $obj->Rotation->Z    = (float)$input->roll;  }
		
	}elseif(is_array($input)){
		foreach($input as $key => $value){
			if(in_array($key, $keyNames)){
				if(isset($obj->$key->X) && isset($value->X)){ $obj->$key->X = (float)$value->X; }
				if(isset($obj->$key->Y) && isset($value->Y)){ $obj->$key->Y = (float)$value->Y; }
				if(isset($obj->$key->Z) && isset($value->Z)){ $obj->$key->Z = (float)$value->Z; }
				if(isset($obj->$key->W) && isset($value->W)){ $obj->$key->W = (float)$value->W; }
				//$obj->$key = (float)$value;
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
			$obj->$keyName->W = (float)1.0;
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

function DistanceSquared($a, $b){
	$a = (object)$a;
	$b = (object)$b;
	$dx = $b->x - $a->x;
	$dy = $b->y - $a->y;
	$dz = $b->z - $a->z;
	return ($dx * $dx + $dy * $dy + $dz * $dz);
}

function Distance($a, $b){
	return (sqrt(DistanceSquared($a, $b)));
}

function Distance2dSquared($a, $b){
	$a = (object)$a;
	$b = (object)$b;
	$dx = $b->x - $a->x;
	$dy = $b->y - $a->y;
	return ($dx * $dx + $dy * $dy);
}

function Distance2d($a, $b){
	return (sqrt(Distance2dSquared($a, $b)));
}

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
			//$coords = [ ExtractCoords($data->Mesh1Transform), ExtractCoords($data->Mesh2Transform) ]; // outdated as of July 9th
			$coords = [
				ExtractBoxCenter($data->{"SERIALIZEDSUBCOMP_PuzzleBounds-0"}->Box),
				ExtractBoxCenter($data->{"SERIALIZEDSUBCOMP_PuzzleBounds-1"}->Box),
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
			$coords = [ $platform ] + CombineLocalTransform($actorCoords, $rings);
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

