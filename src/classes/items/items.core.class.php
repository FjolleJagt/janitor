<?php
/**
* @package janitor.items
*/

/**
* This class holds Items query functionallity.
*
*/

// define default database name constants
// base DB tables
define("UT_ITEMS",              SITE_DB.".items");                             // Items

// MEDIAE
define("UT_ITEMS_MEDIAE",       SITE_DB.".items_mediae");                      // Items Mediae

// COMMENTS
define("UT_ITEMS_COMMENTS",     SITE_DB.".items_comments");                    // Items Comments

// TAGS
define("UT_TAG",                SITE_DB.".tags");                              // Item tags
define("UT_TAGGINGS",           SITE_DB.".taggings");                          // Item tags relations


// SHOP EXTENSIONS
define("UT_PRICES",             SITE_DB.".prices");                            // Item prices

define("UT_LANGUAGES",          SITE_DB.".languages");                         // Languages
define("UT_COUNTRIES",          SITE_DB.".countries");                         // Countries

define("UT_CURRENCIES",         SITE_DB.".currencies");                        // Currencies
define("UT_VATRATES",           SITE_DB.".vatrates");                          // Vatrates



class ItemsCore {

	/**
	* Init, set varnames, validation rules
	*/
	function __construct() {


	}

	/**
	* Get matching type object instance
	*
	* @return return instance of type object
	*/
	function TypeObject($itemtype) {

		// TODO: is mixed needed anymore?
		// include generic type (for mixed itemtypes)
		if($itemtype == "mixed" || !$itemtype) {
			$itemtype = "mixed";
			$class = "TypeMixed";
		}
		else {
			$class = "Type".ucfirst($itemtype);
		}

		if(!isset($this->itemtypes["class"][$itemtype])) {
			include_once("classes/items/type.$itemtype.class.php");
			$this->itemtypes["class"][$itemtype] = new $class();

		}
		return $this->itemtypes["class"][$itemtype];
	}


	/**
	* Global getItem
	* Get item data from items db - does not did any deeper into type object
	*
	* @param $_options Named Array containing id or sindex to get
	*/
	function getItem($_options = false) {

		$id = false;
		$sindex = false;
		$extend = false;

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "id"        : $id             = $_value; break;
					case "sindex"    : $sindex         = $_value; break;

					case "extend"     : $extend        = $_value; break;
				}
			}
		}


		$query = new Query();
		$sql = false;
		if($id) {
			$sql = "SELECT * FROM ".UT_ITEMS." WHERE id = '$id'";
		}
		else if($sindex) {
			$sql = "SELECT * FROM ".UT_ITEMS." WHERE sindex = '$sindex'";
		}
//		print $sql."<br>";

		if($sql && $query->sql($sql)) {
			$item = $query->result(0);


			if($extend) {
				// only pass on extend settings if they are not empty
				$item = $this->extendItem($item, (is_array($extend) ? $extend : false));
			}
			return $item;
		}

		return false;
	}

	/**
	* Get ID of item based on sindex
	*/
	function getIdFromSindex($sindex) {

		$query = new Query();
		$sql = "SELECT id FROM ".UT_ITEMS." WHERE sindex = '$sindex'";
		if($query->sql($sql)) {
			return $query->result(0, "id"); 
		}

		return false;
	}

	/**
	* Global getCompleteItem (both getItem and get on itemtype)
	* + tags
	* + prices
	* + ratings
	* + comments
	*
	* @param $_options Named Array containing id or sindex to get
	*/
	// function getCompleteItem($_options = false) {
	//
	// 	$item = $this->getItem($_options);
	// 	if($item) {
	//
	// 		// get the specific type data
	// 		$typeObject = $this->TypeObject($item["itemtype"]);
	// 		if(method_exists($typeObject, "get")) {
	// 			$item = array_merge($item, $typeObject->get($item["id"]));
	// 		}
	// 		else {
	// 			$item = array_merge($item, $this->getSimpleType($item["id"], $typeObject));
	// 		}
	//
	// 		// add prices and tags
	// 		$item["prices"] = $this->getPrices(array("item_id" => $item["id"]));
	// 		$item["tags"] = $this->getTags(array("item_id" => $item["id"]));
	//
	// 		// TODO: add comments and ratings
	// 		// $item["ratings"] = $this->getRatings(array("item_id" => $item["id"]));
	// 		// $item["comments"] = $this->getComments(array("item_id" => $item["id"]));
	//
	// 		return $item;
	// 	}
	// 	return false;
	// }


	/**
	* Extend item (already having base information)
	* Defined to be able to limit queries when getting information
	*
	* Default only gets type data
	*
	* Optional data
	*/
	function extendItem($item, $_options = false) {

		if(isset($item["id"]) && isset($item["itemtype"])) {

			$user = false;
			$mediae = false;
			$tags = false;
			$prices = false;
			$ratings = false;
			$comments = false;

			// global setting for getting everything
			$all = false;

			if($_options !== false) {
				foreach($_options as $_option => $_value) {
					switch($_option) {

						case "user"         : $user           = $_value; break;
						case "mediae"       : $mediae         = $_value; break;
						case "tags"         : $tags           = $_value; break;
						case "prices"       : $prices         = $_value; break;
						case "ratings"      : $ratings        = $_value; break;
						case "comments"     : $comments       = $_value; break;

						case "all"          : $all            = $_value; break;
					}
				}
			}


			// get the specific type data
			$typeObject = $this->TypeObject($item["itemtype"]);
			if(method_exists($typeObject, "get")) {
				$item = array_merge($item, $typeObject->get($item["id"]));
			}
			else {
				$item = array_merge($item, $this->getSimpleType($item["id"], $typeObject));
			}

			// add mediae
			if($all || $mediae) {
				$item["mediae"] = $this->getMediae(array("item_id" => $item["id"]));
			}

			// add comments
			if($all || $comments) {
				$item["comments"] = $this->getComments(array("item_id" => $item["id"]));
			}

			// add prices
			if($all || $prices) {
				$item["prices"] = $this->getPrices(array("item_id" => $item["id"]));
			}

			// add tags
			if($all || $tags) {
				$item["tags"] = $this->getTags(array("item_id" => $item["id"]));
			}

			// add user nickname
			if($all || $user) {
				$UC = new User();
				$user = $UC->getUserinfo(array("user_id" => $item["user_id"]));
				$item["user_nickname"] = $user ? $user["nickname"] : "N/A";
			}

			// TODO: Implement ratings and comments
			// NOT IMPLEMENTED YET
			// if($everything || $ratings) {
			//	$item["ratings"] = $this->getRatings(array("item_id" => $item["id"]));
			// }
			// if($everything || $comments) {
			//	$item["comments"] = $this->getComments(array("item_id" => $item["id"]));
			// }

			return $item;
		}
		return false;
	}

	function extendItems($items, $_options = false) {

		if($items) {
			foreach($items as $i => $item) {
				// only pass on extend settings if they are not empty
				$items[$i] = $this->extendItem($item, (is_array($_options) ? $_options : false));
			}
		}

		return $items;
	}



	/**
	* Get simple (flat) item type
	* Defined to handle basic type data
	*
	* When creating complex itemtypes with multiple tables involved in data structure
	* override this by adding a get function to your type object
	*/
	function getSimpleType($item_id, $typeObject) {
		$query = new Query();

		$sql = "SELECT * FROM ".$typeObject->db." WHERE item_id = $item_id";
		if($query->sql($sql)) {
			$item = $query->result(0);
			unset($item["id"]);

			return $item;
		}
		return false;
	}


	/**
	* Related items
	*
	* Looks for items of same itemtype, with the best matching tags
	* Otherwise find random items
	*/
	function getRelatedItems($_options = false) {

		$related_items = array();
		$limit = 5;
		$limit = 5;
		$extend = false;
		$exclude_array = array();

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "itemtype"   : $itemtype   = $_value; break;
					case "tags"       : $tags       = $_value; break;
					case "limit"      : $limit      = $_value; break;

					case "exclude"    : $exclude    = $_value; break;

					case "extend"     : $extend     = $_value; break;
				}
			}
		}

		if(isset($exclude)) {
			$exclude_array = explode(";", $exclude);
		}

		// if tags are available make complex query
		if($tags) {

			$query = new Query();

			$SELECT = array();
			$FROM = array();
			$LEFTJOIN = array();
			$WHERE = array();
			$GROUP_BY = "";
			$ORDER = array();
			$LIMIT = "";

			$SELECT[] = "items.id";
			$SELECT[] = "items.sindex";
			$SELECT[] = "items.status";
			$SELECT[] = "items.itemtype";
			$SELECT[] = "items.user_id";

			$SELECT[] = "items.created_at";
			$SELECT[] = "items.modified_at";
			$SELECT[] = "items.published_at";

		 	$FROM[] = UT_ITEMS." as items";

			$WHERE[] = "items.status = 1";

			// add exclude exceptions
			foreach($exclude_array as $exclude_id) {
				$WHERE[] = "items.id != $exclude_id";
			}

			// add itemtype if available
			if(isset($itemtype)) {
				$WHERE[] = "items.itemtype = '$itemtype'";
			}

			// tag query
			$LEFTJOIN[] = UT_TAGGINGS." as taggings ON taggings.item_id = items.id";
			$LEFTJOIN[] = UT_TAG." as tags ON tags.id = taggings.tag_id";

			// create tag SQL
			$tag_sql = "";
			foreach($tags as $tag) {
				$tag_sql .= ($tag_sql ? " OR " : "") .  "tags.context = '".$tag["context"]."' AND tags.value = '".$tag["value"]."'";
			}
			$WHERE[] = "(".$tag_sql.")";

			// Order result for best matches first
			$ORDER[] = "count(*) DESC, published_at DESC";
			$GROUP_BY = "items.id";

			// set limit
			if(isset($limit)) {
				$LIMIT = " LIMIT $limit";
			}

			$sql = $query->compileQuery($SELECT, $FROM, array("LEFTJOIN" => $LEFTJOIN, "WHERE" => $WHERE, "GROUP_BY" => $GROUP_BY, "ORDER" => $ORDER)) . $LIMIT;
//			print $sql."<br>\n";

			$query->sql($sql);
			$related_items = $query->results();

		}

		// update exclude values to exlude any tags matches
		if($related_items) {
			foreach($related_items as $item) {
				$exclude_array[] = $item["id"];
			}
		}


		// not enough related items found 
		// (or no tags was included in request)
		if(count($related_items) < $limit) {

			// create search pattern
			$pattern = array("status" => 1);

			// add itemtype to search pattern if possible
			if($itemtype) {
				$pattern["itemtype"] = $itemtype;
			}
			if($exclude_array) {
				$pattern["exclude"] = implode(";", $exclude_array);
			}

			$pattern["limit"] = $limit - count($related_items);
			$pattern["order"] = "RAND()";

			// find some suitable random items 
			$items = $this->getItems($pattern);

			// merge items to get the combined stack
			$related_items = array_merge($related_items, $items);
		}

		// should items be extended?
		if(isset($extend)) {
			$related_items = $this->extendItems($related_items, $extend);
		}

		return $related_items;
	}


	/**
	* Get all matching items
	*
	* @param String $options
	* $order      String  - 
	* $status     Int 
	* $tags       
	* $sindex
	* $itemtype (match itemtype) 
	* $limit (limit of returned result)
	* $user_id (match user id)
	* $exclude (;-separated item id's to exclude)
	* $extend (automatically extend items with itemtype info before returning)
	*
	* @param String $sindex Optional navigation index - s(earch)index
	*
	* @return Array [id][] + [itemtype][]
	*/
	function getItems($_options = false) {

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "itemtype"      : $itemtype   = $_value; break;
					case "status"        : $status     = $_value; break;
					case "tags"          : $tags       = $_value; break;
					case "sindex"        : $sindex     = $_value; break;
					case "order"         : $order      = $_value; break;
					case "limit"         : $limit      = $_value; break;

					case "user_id"       : $user_id    = $_value; break;

					case "exclude"       : $exclude    = $_value; break;

					case "extend"        : $extend     = $_value; break;
					
					// TODO: implement date ranges

					// TODO: implement search patterns which can also look in local databases - first experiment made in local device search (type.device.class.php)
				}

			}
		}


		$query = new Query();

		$SELECT = array();
		$FROM = array();
		$LEFTJOIN = array();
		$WHERE = array();
		$GROUP_BY = "";
		$HAVING = "";
		$ORDER = array();


		$SELECT[] = "items.id";
		$SELECT[] = "items.sindex";
		$SELECT[] = "items.status";
		$SELECT[] = "items.itemtype";
		$SELECT[] = "items.user_id";

		$SELECT[] = "items.created_at";
		$SELECT[] = "items.modified_at";
		$SELECT[] = "items.published_at";

	 	$FROM[] = UT_ITEMS." as items";

		if(isset($status)) {
			$WHERE[] = "items.status = $status";
		}

		if(isset($user_id)) {
			$WHERE[] = "items.user_id = $user_id";
		}

		if(isset($exclude)) {
			$exclude_array = explode(";", $exclude);
			foreach($exclude_array as $exclude_id) {
				$WHERE[] = "items.id != $exclude_id";
			}
		}

		// TODO: implement dateranges
		// if(isset($published_at)) {
		// 	$WHERE[] = "items.published_at = $published_at";
		// }

		if(isset($itemtype)) {
			$WHERE[] = "items.itemtype = '$itemtype'";

			// add main itemtype table to enable sorting based on local values
			$LEFTJOIN[] = $this->typeObject($itemtype)->db." as ".$itemtype." ON items.id = ".$itemtype.".item_id";
		}

		// tag query

		if(isset($tags) && is_string($tags)) {

			$LEFTJOIN[] = UT_TAGGINGS." as taggings ON taggings.item_id = items.id";
			$LEFTJOIN[] = UT_TAG." as tags ON tags.id = taggings.tag_id";


//			$FROM[] = UT_TAGGINGS . " as item_tags";
//			$FROM[] = UT_TAG . " as tags";
//			$tag_array = explode(",", $tags);
			// UPDATED: changed tags separator to ;
			$tag_array = explode(";", $tags);
			$tag_sql = "";


			foreach($tag_array as $tag) {
//				$exclude = false;
				// tag id
				if($tag) {

					// dechipher tag
					$exclude = false;

					// negative tag, exclude
					if(substr($tag, 0, 1) == "!") {
						$tag = substr($tag, 1);
						$exclude = true;
					}

					// if tag has both context and value
					if(strpos($tag, ":")) {
						list($context, $value) = explode(":", $tag);
					}
					// only context present, value false
					else {
						$context = $tag;
						$value = false;
					}

					if($context || $value) {
						// Negative !tag
						if($exclude) {
//							$WHERE[] = "items.id NOT IN (SELECT item_id FROM ".UT_TAGGINGS." as item_tags, ".UT_TAG." as tags WHERE item_tags.tag_id = tags.id" . ($context ? " AND tags.context = '$context'" : "") . ($value ? " AND tags.value = '$value'" : "") . ")";
//							$WHERE[] = "items.id NOT IN (SELECT item_id FROM ".UT_TAGGINGS." as item_tags, ".UT_TAG." as tags WHERE item_tags.tag_id = tags.id" . ($context ? " AND tags.context = '$context'" : "") . ($value ? " AND tags.value = '$value'" : "") . ")";
						}
						// positive tag
						else {
							if($context && $value) {
								$tag_sql .= ($tag_sql ? " OR " : "") .  "tags.context = '$context' AND tags.value = '$value'";
							}
							else if($context) {
								$tag_sql .= ($tag_sql ? " OR " : "") .  "tags.context = '$context'";
							}
//							$WHERE[] = "items.id IN (SELECT item_id FROM ".UT_TAGGINGS." as item_tags, ".UT_TAG." as tags WHERE item_tags.tag_id = tags.id" . ($context ? " AND tags.context = '$context'" : "") . ($value ? " AND tags.value = '$value'" : "") . ")";
	//						$WHERE[] = "items.id IN (SELECT item_id FROM ".UT_TAGGINGS." as item_tags, ".UT_TAG." as tags WHERE item_tags.tag_id = '$tag' OR (item_tags.tag_id = tags.id AND tags.name = '$tag'))";
						}
					}
				}
			}
			$WHERE[] = "(".$tag_sql.")";
			$HAVING = "count(*) = ".count($tag_array);
		}


		$GROUP_BY = "items.id";


		// add item-order specific SQL
		if(isset($order)) {
			$ORDER[] = $order;
		}

		$ORDER[] = "items.published_at DESC";

		if(isset($limit)) {
			$limit = " LIMIT $limit";
		}
		else {
			$limit = "";
		}

		$items = array();

		$sql = $query->compileQuery($SELECT, $FROM, array("LEFTJOIN" => $LEFTJOIN, "WHERE" => $WHERE, "HAVING" => $HAVING, "GROUP_BY" => $GROUP_BY, "ORDER" => $ORDER)) . $limit;
//		print $sql."<br>\n";

		$query->sql($sql);
		$items = $query->results();


		// TODO: consider if this could be integrated in primary query
		// - but might give issues with flexibility and query load on mixed lists
		// needs to be investigated
		if(isset($extend)) {
			$items = $this->extendItems($items, $extend);
		}

		return $items;
	}




	// PAGINATION STUFF


	/**
	* Get next item(s)
	*
	* Can receive items array to use for finding next item(s) 
	* or receive query syntax to perform getItems request on it own
	*
	* @param $item_id item_id to get next from
	*/
	function getNext($item_id, $_options=false) {

		$items = false;
		$count = 1;

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "items"   : $items    = $_value; break;
					case "count"   : $count    = $_value; break;
				}
			}
		}

		// get items if they have not been passed as argument
		if($items === false) {
			$items = $this->getItems($_options);
		}

		// filtering variables
		$next_items = array();
		$item_found = false;
		$counted = 0;

		// loop through all items, looking for starting point
		for($i = 0; $i < count($items); $i++) {

			// wait until we find starting point
			if($item_found) {

				// keep an eye on counter
				$counted++;

				// add to next scope
				$next_items[] = $items[$i];

				// end when enough items have been collected
				if($counted == $count) {
					break;
				}
			}

			// found starting point
			else if($item_id == $items[$i]["id"]) {
				$item_found = true;
			}
		}

		// return set of next items
		return $next_items;
	}

	/**
	* Get previous item(s)
	*
	* Can receive items array to use for finding previous item(s) 
	* or receive query syntax to perform getItems request on it own
	* TODO: This implementation is far from performance optimized, but works - consider alternate implementations
	*/
	function getPrev($item_id, $_options=false) {

		$items = false;
		$count = 1;

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "items"   : $items    = $_value; break;
					case "count"   : $count    = $_value; break;
				}
			}
		}

		// get items if they have not been passed as argument
		if($items === false) {
			$items = $this->getItems($_options);
		}

		
		// filtering variables
		$prev_items = array();
		$item_found = false;
		$counted = 0;
		// loop backwards through all items, looking for starting point
		for($i = count($items)-1; $i >= 0; $i--) {

			// wait until we find starting point
			if($item_found) {

				// keep an eye on counter
				$counted++;

				// add to beginning of prev scope
				array_unshift($prev_items, $items[$i]);

				// end when enough items have been collected
				if($counted == $count) {
					break;
				}
			}

			// found starting point
			else if($item_id == $items[$i]["id"]) {
				$item_found = true;
			}
		}

		// return set of prev items
		return $prev_items;
	}


	// Paginate items list
	// split up into smaller fragments and return information required to
	// create meaningful pagination
	function paginate($_options) {

		$direction = false;
		$id = false;
		$sindex = false;
		$pattern = false;

		$limit = 5;

		$extend = false;

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "pattern"              : $pattern         = $_value; break;

					case "limit"                : $limit           = $_value; break;
					case "sindex"               : $sindex          = $_value; break;
					case "id"                   : $id              = $_value; break;

					case "direction"            : $direction       = $_value; break;
				}
			}
		}

		
		// avoid extending all items
		// but save extend values to extend final range_items
		if(isset($pattern["extend"])) {
			$extend = $pattern["extend"];
			unset($pattern["extend"]);
		}


		// get all items as base for pagination
		$items = $this->getItems($pattern);


		// if there is no sindex to start from
		// lists the latest N posts
		if(!$sindex) {

			// simply add limit to items query
			$pattern["limit"] = $limit;
			$range_items = $this->getItems($pattern);

		}

		// range_items should be based on sindex
		else if($sindex) {

			// get the item_id based on sindex
			$item_id = $this->getIdFromSindex($sindex);

			// Lists the next N posts _after_ sindex (not including)
			if($direction == "next") {

				$range_items = $this->getNext($item_id, array("items" => $items, "count" => $limit));
			}
			// Lists the prev N posts _before_ sindex (not including)
			else if($direction == "prev") {

				$range_items = $this->getPrev($item_id, array("items" => $items, "count" => $limit));
			}

			// No direction indicated
			// Lists the next N posts _starting_ with sindex
			else {

				// filtering variables
				$item_found = false;
				$counted = 0;

				// loop through all items, looking for starting point
				for($i = 0; $i < count($items); $i++) {

					// found starting point
					if($item_id == $items[$i]["id"]) {
						$item_found = true;
					}

					// wait until we find starting point
					if($item_found) {

						// keep an eye on counter
						$counted++;

						// add to next scope
						$range_items[] = $items[$i];

						// end when enough items have been collected
						if($counted == $limit) {
							break;
						}
					}

				}

			}

		}

		// Should range items be extended, then do it now
		if($range_items && $extend) {
			foreach($range_items as $i => $item) {
				$range_items[$i] = $this->extendItem($item, $extend);
			}
		}


		// find indexes and ids for next/prev
		$first_id = isset($range_items[0]) ? $range_items[0]["id"] : false;
		$first_sindex = isset($range_items[0]) ? $range_items[0]["sindex"] : false;
		$last_id = isset($range_items[count($range_items)-1]) ? $range_items[count($range_items)-1]["id"] : false;
		$last_sindex = isset($range_items[count($range_items)-1]) ? $range_items[count($range_items)-1]["sindex"] : false;

		// look for next/prev item availability
		$next = $last_id ? $this->getNext($last_id, array("items" => $items, "count" => $limit)) : false;
		$prev = $first_id ? $this->getPrev($first_id, array("items" => $items, "count" => $limit)) : false;


		// return all pagination info
		// range_items = list of items in specified range
		// next items
		// previous items
		// first id in range
		// last id in range
		return array("range_items" => $range_items, "next" => $next, "prev" => $prev, "first_id" => $first_id, "last_id" => $last_id, "first_sindex" => $first_sindex, "last_sindex" => $last_sindex);
	}



	// MEDIA

	// TODO: implement get media function like getTags (needs testing)
	// get mediae, optionally based on item_id
	function getMediae($_options=false) {

		$item_id = false;
		$media_id = false;
		$variant = false;

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "item_id"    : $item_id        = $_value; break;
					case "media_id"   : $media_id       = $_value; break;
					case "variant"    : $variant        = $_value; break;
				}
			}
		}

		$query = new Query();

		// get specific media_id
		if($media_id) {
			if($query->sql("SELECT * FROM ".UT_ITEMS_MEDIAE." WHERE id = '$media_id'")) {
				return $query->result(0);
			}
		}
		else if($item_id) {

			// specific media variant?
			if($variant) {
				if($query->sql("SELECT * FROM ".UT_ITEMS_MEDIAE." WHERE variant = '$variant' AND item_id = $item_id")) {
					return $query->result(0);
				}
			}

			// all mediae (not HTML-editor media)
			else {

				$sql = "SELECT * FROM ".UT_ITEMS_MEDIAE." WHERE item_id = $item_id AND variant NOT LIKE 'HTML-%' ORDER BY position ASC, id DESC";
//				print $sql."<br>\n";

				if($query->sql($sql)) {
					$mediae = array();
					$results = $query->results();
					foreach($results as $result) {
						$mediae[$result["variant"]] = $result;
					}
					return $mediae;
				}
			}
		}

		// get all mediae
		else {

			if($query->sql("SELECT * FROM ".UT_ITEMS_MEDIAE)) {
				return $query->results();
			}
			
		}
		return false;
	}


	// find media with matching variant or simply first media
	// removes media from media stack (to make it easier to loop through remaining media later)
	function sliceMedia(&$item, $variant=false) {

		$media = false;

		if(!$variant && isset($item["mediae"]) && $item["mediae"]) {
			$media = array_shift($item["mediae"]);
		}
		else if(isset($item[$variant])) {

			$media = $item[$variant];
			unset($item[$variant]);
		}
		else if(isset($item["mediae"]) && $item["mediae"]) {
			foreach($item["mediae"] as $index => $media_item) {
				if($index == $variant) {

					$media = $item["mediae"][$variant];
					unset($item["mediae"][$variant]);
				}
			}
		}

		return $media;
	}



	// TAGS


	// get tag, optionally based on item_id, limited to context, or just check if specific tag exists
	function getTags($_options=false) {

		$item_id = false;
		$tag_id = false;
		$tag_context = false;
		$tag_value = false;
		$order = false;

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "item_id"    : $item_id        = $_value; break;
					case "tag_id"     : $tag_id         = $_value; break;
					case "context"    : $tag_context    = $_value; break;
					case "value"      : $tag_value      = $_value; break;
					case "order"      : $order          = $_value; break;
				}
			}
		}

		$query = new Query();


		if($item_id) {
			// specific tag exists?
			if($tag_context && $tag_value) {
				return $query->sql("SELECT * FROM ".UT_TAG." as tags, ".UT_TAGGINGS." as taggings WHERE tags.context = '$tag_context' AND tags.value = '$tag_value' AND tags.id = taggings.tag_id AND taggings.item_id = $item_id");
			}
			// get all tags with context
			else if($tag_context) {
				if($query->sql("SELECT tags.id as id, tags.context as context, tags.value as value FROM ".UT_TAG." as tags, ".UT_TAGGINGS." as taggings WHERE tags.context = '$tag_context' AND tags.id = taggings.tag_id AND taggings.item_id = $item_id".($order ? " ORDER BY $order" : ""))) {
					return $query->results();
				}
			}
			// all tags
			else {
				if($query->sql("SELECT tags.id as id, tags.context as context, tags.value as value FROM ".UT_TAG." as tags, ".UT_TAGGINGS." as taggings WHERE tags.id = taggings.tag_id AND taggings.item_id = $item_id".($order ? " ORDER BY $order" : ""))) {
					return $query->results();
				}
			}
		}
		// get tag and items using tag_id
		else if($tag_id) {
			$query->sql("SELECT * FROM ".UT_TAG." as tags WHERE tags.id = '$tag_id'");
			$tag = $query->result(0);
			
			$sql = "SELECT item_id as id, itemtype, status FROM ".UT_TAGGINGS." as taggings, ".UT_ITEMS." as items WHERE taggings.tag_id = '$tag_id' AND taggings.item_id = items.id";
//			print $sql;
			$query->sql($sql);
			$tag["items"] = $query->results();
			return $tag;
		}
		// get items using tag with context and value
		else if($tag_context && $tag_value) {
			$query->sql("SELECT * FROM ".UT_TAG." as tags, ".UT_TAGGINGS." as taggings WHERE tags.context = '$tag_context' AND tags.value = '$tag_value' AND tags.id = taggings.tag_id");
			return $query->results();
		}
		// get all tags
		else {
			// get all tags with context
			if($tag_context) {
				if($query->sql("SELECT tags.id as id, tags.context as context, tags.value as value FROM ".UT_TAG." as tags WHERE tags.context = '$tag_context'".($order ? " ORDER BY $order" : ""))) {
					return $query->results();
				}
			}
			// all tags
			else {
				if($query->sql("SELECT tags.id as id, tags.context as context, tags.value as value FROM ".UT_TAG.($order ? " ORDER BY $order" : " ORDER BY tags.context, tags.value"))) {
					return $query->results();
				}
			}
			
		}
		return false;
	}




	// COMMENTS


	// get tag, optionally based on item_id, limited to context, or just check if specific tag exists
	function getComments($_options=false) {

		$item_id = false;
		$comment_id = false;
		$user_id = false;
		$order = false;

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "item_id"     : $item_id        = $_value; break;
					case "comment_id"  : $comment_id     = $_value; break;
					case "user_id"     : $user_id        = $_value; break;

					case "order"       : $order          = $_value; break;
				}
			}
		}

		$query = new Query();

		// Get all comments for item_id
		if($item_id) {

			$sql = "SELECT comments.id, comments.comment, comments.created_at, users.nickname FROM ".UT_ITEMS_COMMENTS." as comments, ".SITE_DB.".users as users WHERE comments.item_id = $item_id AND comments.user_id = users.id".($order ? " ORDER BY $order" : "");
			if($query->sql($sql)) {
				return $query->results();
			}
		}
		// Get all comments by user_id and related items
		else if($user_id) {
			if($query->sql("SELECT * FROM ".UT_ITEMS_COMMENTS." as comments WHERE user_id = $user_id".($order ? " ORDER BY $order" : ""))) {
				$comments = $query->results();
				foreach($comments as $index => $comment) {
					$comments[$index]["item"] = $this->getItem(array("id" => $comment["item_id"], "extend" => true));
				}
				return $comments;
			}
		}
		// get comment using comment_id
		else if($comment_id) {
			if($query->sql("SELECT comments.id, comments.comment, comments.created_at, users.nickname FROM ".UT_ITEMS_COMMENTS." as comments, ".SITE_DB.".users as users WHERE comments.id = '$comment_id' AND comments.user_id = users.id")) {
				return $query->result(0);
			}
		}
		// get all comments
		else {
			if($query->sql("SELECT comments.id, comments.comment, comments.created_at, users.nickname FROM ".UT_ITEMS_COMMENTS." as comments, ".SITE_DB.".users as users WHERE comments.user_id = users.id" . ($order ? " ORDER BY $order" : " ORDER BY created_at"))) {
				return $query->results();
			}
		}
		return false;
	}





	// PRICES - EARLY IMPLEMENTATIONS


	// TODO: temporary price handler (should be updated when currencies are finalized)
	// extend price array with calulations
	// if currency is stated, just return one price
	//
	function extendPrices($prices, $_options = false) {

		$currency = false;

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {

					case "currency"  : $currency   = $_value; break;
				}
			}
		}

		if($currency) {

			foreach($prices as $index => $price) {
				if($currency == $prices[$index]["currency"]) {
					$prices[$index]["price_with_vat"] = $price["price"] * (1 + ($price["vatrate"]/100));
					$prices[$index]["vat_of_price"] = $price["price"] * ($price["vatrate"]/100);

					return $prices[$index];
				}
			}
		}
		else {

			foreach($prices as $index => $price) {
				if(!$currency || $currency == $prices[$index]["currency"]) {
					$prices[$index]["price_with_vat"] = $price["price"]* (1 + ($price["vatrate"]/100));
					$prices[$index]["vat_of_price"] = $price["price"] * ($price["vatrate"]/100);
				}
			}
		}

		return $prices;

	}

	// get prices, 
	// TODO: extend to be able to get items ordered by price if possible
	// TODO: could/should be merged with extendPrices when currencies are finalized
	function getPrices($_options = false) {

		$item_id = false;

		$currency = false;
		$country = false;

		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "item_id"   : $item_id    = $_value; break;

					case "currency"  : $currency   = $_value; break;
					case "country"   : $country    = $_value; break;
				}
			}
		}

		$prices = array();
		$query = new Query();

		if($country && !$currency) {
			if($query->sql("SELECT currency FROM ".UT_COUNTRIES." WHERE id = '$country' LIMIT 1")) {
				$currency = $query->result(0, "currency");
			}
		}

		if($currency) {
			if($query->sql("SELECT * FROM ".UT_PRICES.", ".UT_CURRENCIES.", ".UT_VATRATES." WHERE vatrate_id = ".UT_VATRATES.".id AND currency = '$currency' AND item_id = $item_id")) {
				$prices = $query->results();
			}
		}
		else {
			if($query->sql("SELECT * FROM ".UT_PRICES.", ".UT_CURRENCIES.", ".UT_VATRATES." WHERE vatrate_id = ".UT_VATRATES.".id AND item_id = $item_id")) {
				$prices = $query->results();
			}
		}

		return $prices;
	}



}

?>