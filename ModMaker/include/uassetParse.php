<?php

include_once("include\\file_io.php");

function &ParseUassetValueNode(string $type, &$node_ref){
	
	static $error = null;
	
	switch($type){
		
		case "UAssetAPI.ExportTypes.NormalExport, UAssetAPI":
		case "UAssetAPI.ExportTypes.LevelExport, UAssetAPI":
			$dataArr = [];
			foreach($node_ref as $index => &$subNode_ref){
				if(isset($subNode_ref->Value)){
					$subName = $subNode_ref->Name;
					$subType = $subNode_ref->{'$type'};
					$dataArr[$subName] = &ParseUassetValueNode($subType, $subNode_ref->Value);
				}
			}unset($subNode_ref);
			//return (object)$dataArr;
			return $dataArr;
			
		case "UAssetAPI.PropertyTypes.Objects.SetPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.ArrayPropertyData, UAssetAPI":
			//$isStruct = ($node_ref->ArrayType == "StructProperty");
			if(count($node_ref) == 1){
				if(isset($node_ref[0]->Value)){
					return ParseUassetValueNode($node_ref[0]->{'$type'}, $node_ref[0]->Value);
				}else{
					return null;
				}
			}
			$valueArr = [];
			foreach($node_ref as $index => &$subNode_ref){
				if(isset($subNode_ref->Value)){
					$subType = $subNode_ref->{'$type'};
					$valueArr[] = &ParseUassetValueNode($subType, $subNode_ref->Value);
				}
			}unset($subNode_ref);
			return $valueArr;
			
		case "UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.EnumPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.IntPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.FloatPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.StrPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.ObjectPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.NamePropertyData, UAssetAPI": // ?
		case "UAssetAPI.PropertyTypes.Objects.TextPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.GuidPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.ColorPropertyData, UAssetAPI":
			return $node_ref;
			
		case "UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.MulticastSparseDelegatePropertyData, UAssetAPI":
		
			$fieldArr = [];
			if(count($node_ref) == 1){
				if(isset($node_ref[0]->Value)){
					return ParseUassetValueNode($node_ref[0]->{'$type'}, $node_ref[0]->Value);
				}else{
					return $error;
				}
			}
			foreach($node_ref as $index => &$subNode_ref){
				if(isset($subNode_ref->Value)){
					$subName = $subNode_ref->Name;
					$subType = $subNode_ref->{'$type'};
					
					$finalSubName = $subName;
					$i = 2;
					while(isset($fieldArr[$finalSubName])){
						$finalSubName = $subName . $i;
						++$i;
					}
					//if(isset($fieldArr[$finalSubName])){
					//	printf("ERROR: duplicate field name %s\n", $finalSubName);
					//	exit(1);
					//}
					$fieldArr[$finalSubName] = &ParseUassetValueNode($subType, $subNode_ref->Value);
				}
			}unset($subNode_ref);
			return $fieldArr;
			
		case "UAssetAPI.PropertyTypes.Structs.BoxPropertyData, UAssetAPI":
			//$node_ref->{'Min'}->X = (float)$node_ref->{'Min'}->X; // please don't
			//$node_ref->{'Min'}->Y = (float)$node_ref->{'Min'}->Y; // please don't
			//$node_ref->{'Min'}->Z = (float)$node_ref->{'Min'}->Z; // please don't
			//$node_ref->{'Max'}->X = (float)$node_ref->{'Max'}->X; // please don't
			//$node_ref->{'Max'}->Y = (float)$node_ref->{'Max'}->Y; // please don't
			//$node_ref->{'Max'}->Z = (float)$node_ref->{'Max'}->Z; // please don't
			$custom = (object)[
				"MinX" => &$node_ref->{'Min'}->X,
				"MinY" => &$node_ref->{'Min'}->Y,
				"MinZ" => &$node_ref->{'Min'}->Z,
				"MaxX" => &$node_ref->{'Max'}->X,
				"MaxY" => &$node_ref->{'Max'}->Y,
				"MaxZ" => &$node_ref->{'Max'}->Z,
			];
			return $custom;
		
		case "UAssetAPI.PropertyTypes.Structs.QuatPropertyData, UAssetAPI":
			//$node_ref->X = (float)$node_ref->X; // please don't
			//$node_ref->Y = (float)$node_ref->Y; // please don't
			//$node_ref->Z = (float)$node_ref->Z; // please don't
			//$node_ref->W = (float)$node_ref->W; // please don't
			$custom = (object)[
				"X" => &$node_ref->X,
				"Y" => &$node_ref->Y,
				"Z" => &$node_ref->Z,
				"W" => &$node_ref->W,
			];
			return $custom;
		
		case "UAssetAPI.PropertyTypes.Structs.Vector2DPropertyData, UAssetAPI":
			//$node_ref->X = (float)$node_ref->X; // please don't
			//$node_ref->Y = (float)$node_ref->Y; // please don't
			$custom = (object)[
				"X" => &$node_ref->X,
				"Y" => &$node_ref->Y,
			];
			return $custom;
			
		case "UAssetAPI.PropertyTypes.Structs.VectorPropertyData, UAssetAPI":
			//$node_ref->X = (float)$node_ref->X; // please don't
			//$node_ref->Y = (float)$node_ref->Y; // please don't
			//$node_ref->Z = (float)$node_ref->Z; // please don't
			$custom = (object)[
				"X" => &$node_ref->X,
				"Y" => &$node_ref->Y,
				"Z" => &$node_ref->Z,
			];
			return $custom;
			
		case "UAssetAPI.PropertyTypes.Structs.RotatorPropertyData, UAssetAPI":
			//$node_ref->Pitch = (float)$node_ref->Pitch; // please don't
			//$node_ref->Yaw   = (float)$node_ref->Yaw;   // please don't
			//$node_ref->Roll  = (float)$node_ref->Roll;  // please don't
			$custom = (object)[
				"Pitch" => &$node_ref->Pitch,
				"Yaw"   => &$node_ref->Yaw,
				"Roll"  => &$node_ref->Roll,
			];
			return $custom;
			
		case "UAssetAPI.PropertyTypes.Structs.LinearColorPropertyData, UAssetAPI":
			$custom = (object)[
				"R" => &$node_ref->R,
				"G" => &$node_ref->G,
				"B" => &$node_ref->B,
				"A" => &$node_ref->A,
			];
			return $custom;
		
		case "UAssetAPI.PropertyTypes.Objects.SoftObjectPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.SoftClassPathPropertyData, UAssetAPI":
			return $node_ref->AssetPath->AssetName;
		
		case "UAssetAPI.PropertyTypes.Objects.BytePropertyData, UAssetAPI":
			return $error; // ?
			
		case "UAssetAPI.PropertyTypes.Objects.MapPropertyData, UAssetAPI":
			if(count($node_ref) == 0){
				return (object)[];
			}
			$mapName = null;
			$map = [];
			foreach($node_ref as $index => &$subNode_ref){
				if(!is_array($subNode_ref) || count($subNode_ref) != 2){
					printf("Malformed MapPropertyData\n");
					exit(1);
				}
				if(isset($subNode_ref[1]->Value)){
					$mapKey_ref   = &($subNode_ref[0])->Value;
					$mapValue_ref = &($subNode_ref[1])->Value;
					if(!is_scalar($mapKey_ref)){
						printf("Error: non-scalar MapPropertyData key |%s|\n", json_encode($mapKey_ref));
						exit(1);
					}
					if($mapName === null){
						$mapName = $subNode_ref[0]->Name;
					}
					$map[$mapKey_ref] = &ParseUassetValueNode($subNode_ref[1]->{'$type'}, $mapValue_ref);
					unset($mapKey_ref);
					unset($mapValue_ref);
				}
			}unset($subNode_ref);
			return $map;
			
		case "UAssetAPI.PropertyTypes.Objects.DelegatePropertyData, UAssetAPI":
			return $node_ref->Delegate; // ?
			
		
		case "System.Byte[], System.Private.CoreLib":
		case "UAssetAPI.CustomVersion, UAssetAPI":
		case "UAssetAPI.ExportTypes.FURL, UAssetAPI":
		case "UAssetAPI.FEngineVersion, UAssetAPI":
		case "UAssetAPI.FGenerationInfo, UAssetAPI":
		case "UAssetAPI.Import, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.FSoftObjectPath, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.FTopLevelAssetPath, UAssetAPI":
		case "UAssetAPI.UAsset, UAssetAPI":
		case "UAssetAPI.UnrealTypes.FQuat, UAssetAPI":
		case "UAssetAPI.UnrealTypes.FRotator, UAssetAPI":
		case "UAssetAPI.UnrealTypes.FVector, UAssetAPI":
		case "UAssetAPI.UnrealTypes.TBox`1[[UAssetAPI.UnrealTypes.FVector, UAssetAPI]], UAssetAPI":
			// Nothing. Handle later
	}
	
	printf("\nERROR: did not parse |%s|:\n%s\n\n", $type, json_encode($node_ref));
	exit(1);
}

function &ParseUassetExports(&$json_ref){
	$exports_ref = &$json_ref->Exports;
	$result = [];
	foreach($exports_ref as $index => &$object_ref){
		$objectName = $object_ref->ObjectName;
		$type = $object_ref->{'$type'};
		
		$info = [];
		
		$info['Data'] = &ParseUassetValueNode($type, $object_ref->Data);
		$info['Data'] = (object)$info['Data'];
		
		$objKeys = array_keys((array)$object_ref);
		foreach($objKeys as $key){
			static $dontInclude = [ '$type', 'Data', 'ObjectName' ];
			if(in_array($key, $dontInclude)){
				continue;
			}
			$value_ref = &$object_ref->$key;
			$info[$key] = &$value_ref;
			unset($value_ref);
		}
		
		$finalName = $objectName;
		$i = 2;
		while(isset($result[$finalName])){
			$finalName = $objectName . "_" . $i;
			++$i;
		}
		$result[$finalName] = (object)($info);
	}unset($object_ref);
	unset($exports_ref);
	return $result;
}

function LoadDecodedUasset(string $path){
	if(!is_file($path)){
		printf("File not found: %s\n", $path);
		exit(1);
	}
	printf("Loading %s...\n", $path);
	$raw = file_get_contents($path);
	if(empty($raw)){
		printf("Failed to load %s\n", $path);
		exit(1);
	}
	$json = json_decode($raw);
	if(empty($json)){
		printf("Failed to load %s\n", $path);
		exit(1);
	}
	return $json;
}

function CompressUassetValueNode(&$node_ref){
	
	$type = $node_ref->{'$type'} ?? "";
	$name = $node_ref->{'Name'} ?? "";
	
	if(is_object($node_ref)){
		unset($node_ref->DuplicationIndex);
		unset($node_ref->IsZero);
		unset($node_ref->StructGUID);
		
		if(isset($node_ref->DummyStruct)){ // do NOT skip this check
			unset($node_ref->DummyStruct->DuplicationIndex);
			unset($node_ref->DummyStruct->IsZero);
			unset($node_ref->DummyStruct->StructGUID);
		}
		//unset($node_ref->DummyStruct); // do NOT do this
		
		$valueExists = (in_array("Value", array_keys((array)$node_ref))); // can't isset
		if($valueExists){
			// Move Value field over to the end of the object.
			$a = $node_ref->Value; unset($node_ref->Value); $node_ref->Value = $a; unset($a);
		}
		$v = $node_ref->Value ?? null;
		if($valueExists && $type != "UAssetAPI.PropertyTypes.Objects.TextPropertyData, UAssetAPI" && ($v == null || (is_array($v) && empty($v)) || (is_scalar($v) && ((!is_string($v) || !strpbrk($v, "|\"\\")))))){
			//printf("Trimming %s\n", substr(json_encode($node_ref, JSON_UNESCAPED_SLASHES), 0, 100));
			$node_ref = "SCALAR_STR_BEGIN{ " . substr(str_replace("\"", "|", str_replace("\",\"", "\", \"", json_encode($node_ref, JSON_UNESCAPED_SLASHES))), 1, -1) . " }SCALAR_STR_END";
			$node_ref = str_replace("|:null,|", "|:null, |", $node_ref); // no idea why but null values don't get a space after the comma in json_encode?!
			return;
		}
		
	}elseif(is_array($node_ref)){
		foreach($node_ref as &$subNode_ref){
			CompressUassetValueNode($subNode_ref);
		}
		return;
	}
	
	switch($type){
		case "UAssetAPI.UAsset, UAssetAPI":
			// Parent node.
			// Move Exports field over to the end of the object.
			$a = $node_ref->Exports; unset($node_ref->Exports); $node_ref->Exports = $a; unset($a);
			// Everything that isn't Exports should be a one-liner. Note: iterating an object here.
			foreach($node_ref as $key => &$value_ref){
				if($key != "Exports" && !is_scalar($value_ref)){
					$value_ref = "META_STR_BEGIN" . str_replace("\"", "|", json_encode($value_ref, JSON_UNESCAPED_SLASHES)) . "META_STR_END";
				}
			}unset($value_ref);
			CompressUassetValueNode($node_ref->Exports);
			return;
		
		case "UAssetAPI.ExportTypes.LevelExport, UAssetAPI":
		case "UAssetAPI.ExportTypes.NormalExport, UAssetAPI":
			// Move Data field over to the end of the object.
			$a = $node_ref->Data; unset($node_ref->Data); $node_ref->Data = $a; unset($a);
			// Everything that isn't Data should be a one-liner. Note: iterating an object here.
			foreach($node_ref as $key => &$value_ref){
				if($key != "Data" && !is_scalar($value_ref)){
					$value_ref = "META_STR_BEGIN" . str_replace("\"", "|", json_encode($value_ref, JSON_UNESCAPED_SLASHES)) . "META_STR_END";
				}
			}unset($value_ref);
			CompressUassetValueNode($node_ref->Data);
			return;
			
		case "UAssetAPI.PropertyTypes.Objects.SetPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.ArrayPropertyData, UAssetAPI":
			$isStruct = (isset($node_ref->ArrayType) && $node_ref->ArrayType == "StructProperty");
			// TODO sort by Name field (actually index) and make sure it's sequential (0..count-1).
			foreach($node_ref->Value as $index => &$subNode_ref){
				if(!$isStruct){
					unset($subNode_ref->Name); // remove array indices.
				}
				CompressUassetValueNode($subNode_ref);
			}unset($subNode_ref);
			
			if($name == "Solves" && $isStruct){
				//$node_ref->Value = "SOLVES_STR_BEGIN" . str_replace("\"", "|", json_encode($node_ref->Value, JSON_UNESCAPED_SLASHES)) . "SOLVES_STR_END"; // this works!
				// Turn to a string.
				$node_ref->Value = json_encode($node_ref->Value, JSON_UNESCAPED_SLASHES);
				// Remove scalar markers.
				$node_ref->Value = preg_replace_callback("/\"SCALAR_STR_BEGIN(.*?)SCALAR_STR_END\"/", function ($matches) { return (str_replace("|", "\"", $matches[1])); }, $node_ref->Value);
				// Re-encode as a very compressed one-liner json.
				$node_ref->Value = json_encode(json_decode($node_ref->Value), JSON_UNESCAPED_SLASHES);
				// Turn into a temporary string so that it's not expanded back later.
				$node_ref->Value = "SOLVES_STR_BEGIN" . str_replace("\"", "|", $node_ref->Value) . "SOLVES_STR_END";
			}
			return;
			
		case "UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.MulticastSparseDelegatePropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.MapPropertyData, UAssetAPI":
			CompressUassetValueNode($node_ref->Value);
			return;
			
		case "UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.EnumPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.IntPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.FloatPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.StrPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.ObjectPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.NamePropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.TextPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.GuidPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.ColorPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.BoxPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.QuatPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.Vector2DPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.VectorPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.RotatorPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.SoftObjectPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.SoftClassPathPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.BytePropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.DelegatePropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.FDelegate, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.LinearColorPropertyData, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Structs.RichCurveKeyPropertyData, UAssetAPI":
			return;
			
		case "UAssetAPI.ExportTypes.FunctionExport, UAssetAPI":
		case "UAssetAPI.ExportTypes.ClassExport, UAssetAPI":
			// I don't know what to do with these for now. Probably not something to edit by hand anyway?
			// Turn each field into one-liners for now. Maybe the entire export node should be a one-liner.
			foreach($node_ref as $key => &$value_ref){
				if(!is_scalar($value_ref)){
					$value_ref = "META_STR_BEGIN" . str_replace("\"", "|", json_encode($value_ref, JSON_UNESCAPED_SLASHES)) . "META_STR_END";
				}
			}unset($value_ref);
			return;
		
		case "System.Byte[], System.Private.CoreLib":
		case "UAssetAPI.CustomVersion, UAssetAPI":
		case "UAssetAPI.ExportTypes.FURL, UAssetAPI":
		case "UAssetAPI.FEngineVersion, UAssetAPI":
		case "UAssetAPI.FGenerationInfo, UAssetAPI":
		case "UAssetAPI.Import, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.FSoftObjectPath, UAssetAPI":
		case "UAssetAPI.PropertyTypes.Objects.FTopLevelAssetPath, UAssetAPI":
		case "UAssetAPI.UnrealTypes.FQuat, UAssetAPI":
		case "UAssetAPI.UnrealTypes.FRotator, UAssetAPI":
		case "UAssetAPI.UnrealTypes.FVector, UAssetAPI":
		case "UAssetAPI.UnrealTypes.TBox`1[[UAssetAPI.UnrealTypes.FVector, UAssetAPI]], UAssetAPI":
			// No idea how to handle for now.
	}
	
	printf("\nERROR: did not parse |%s|:\n%s\n\n", $type, json_encode($node_ref));
	exit(1);
}

function SaveCompressedDecodedUasset(string $path, &$json, array $jsonOptions = []){
	
	printf("Compressing json for %s...\n", $path);
	$tabOffsetCount = -2;
	if(count($json->Exports) == 1){
		$tabOffsetCount = -4; // don't ask
	}
	$defaultOptions = [
		"tabs" => 1,
		"tabOffset" => $tabOffsetCount,
		"useTabs" => true,
		"escapeSlashes" => true,
		"prettyPrint" => true,
	];
	$jsonOptions = array_merge($defaultOptions, $jsonOptions);
	
	$copy = unserialize(serialize($json));
	CompressUassetValueNode($copy);
	
	$text = CreateJson($copy, $jsonOptions);
	$text = preg_replace_callback("/\"SOLVES_STR_BEGIN(.*?)SOLVES_STR_END\"/", function ($matches) { return (str_replace("|", "\"", $matches[1])); }, $text);
	$text = preg_replace_callback("/\"SCALAR_STR_BEGIN(.*?)SCALAR_STR_END\"/", function ($matches) { return (str_replace("|", "\"", $matches[1])); }, $text);
	$text = preg_replace_callback("/\"META_STR_BEGIN(.*?)META_STR_END\"/",     function ($matches) { return (str_replace("|", "\"", $matches[1])); }, $text);
	$text = strtr($text, [
		"\t\"Value\": false" => "\t\"Value\": 0",
		"\t\"Value\": true"  => "\t\"Value\": 1",
		"\"Value\":false"    => "\"Value\":0",
		"\"Value\":true"     => "\"Value\":1",
		]);
	
	WriteFileSafe($path, $text, true);
}
