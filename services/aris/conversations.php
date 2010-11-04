<?php
require_once("module.php");
require_once("locations.php");
require_once("requirements.php");
require_once("playerStateChanges.php");

class Conversations extends Module
{	

	/**
     * Fetch all conversations for an npc, including node information
     *
     * @param integer $gameId The game identifier
     * @param integer $npcId The game identifier
     * @return returnData
     * @returns a returnData object containing an array of conversations with embedded node info
     * @see returnData
     */
	public function getConversationsWithNodeForNpc($gameId, $npcId)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT {$prefix}_npc_conversations.*,
					{$prefix}_npc_conversations.text AS conversation_text,{$prefix}_nodes.*
					FROM {$prefix}_npc_conversations JOIN {$prefix}_nodes
					ON {$prefix}_npc_conversations.node_id = {$prefix}_nodes.node_id
					WHERE {$prefix}_npc_conversations.npc_id = {$npcId}";					
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
		return new returnData(0, $rsResult);	
		
	}

	/**
     * Create a conversation and related node for an npc
     *
     * @param integer $gameId The game identifier
     * @param integer $npcId The game identifier
     * @param string $conversationText The conversation link text as wit will appear to the player
     * @param string $nodeText The node script
     
     * @return returnData
     * @returns a returnData object containing the newly created conversation_id and node_id
     * @see returnData
     */
	public function createConversationWithNode($gameId, $npcId, $conversationText, $nodeText)
	{
		
		$conversationText = addslashes($conversationText);	
		$nodeText = addslashes($nodeText);
		$prefix = Module::getPrefix($gameId);
		
		
		
		$query = "INSERT INTO {$prefix}_nodes (text)
					VALUES ('{$conversationText}')";
		NetDebug::trace("createNode: Running a query = $query");	
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	
		
		$newNodeId = mysql_insert_id();
		
			
		$query = "INSERT INTO {$prefix}_npc_conversations (npc_id, node_id, text)
					VALUES ('{$npcId}','{$newNodeId}','{$conversationText}')";
		NetDebug::trace("createConversation: Running a query = $query");	
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	
		
		$newConversationId = mysql_insert_id();
		
		$ids = (object) array('conversation_id' => $newConversationId, 'node_id' => $newNodeId);
	
		return new returnData(0, $ids);
		
	}



	/**
     * Update a conversation and related node for an npc
     *
     * @param integer $gameId The game identifier
     * @param integer $conversationId The conversation identifier
     * @param string $conversationText The conversation link text as wit will appear to the player
     * @param string $nodeText The node script
     
     * @return returnData
     * @returns a returnData object
     * @see returnData
     */
	public function updateConversationWithNode($gameId, $conversationId, $conversationText, $nodeText)
	{
		
		$conversationText = addslashes($conversationText);	
		$nodeText = addslashes($nodeText);
		$prefix = Module::getPrefix($gameId);

		$query = "SELECT node_id FROM {$prefix}_npc_conversations WHERE conversation_id = {$conversationId} LIMIT 1";
		NetDebug::trace("Running a query = $query");	
		$nodeIdRs = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);			
		$nodeIdObject = @mysql_fetch_object($nodeIdRs);
		if (!$nodeIdObject) return new returnData(2, NULL, "No such conversation");			
		$nodeId = $nodeIdObject->node_id;
		
		
		$query = "UPDATE {$prefix}_nodes SET text = '{$nodeText}' WHERE node_id = {$nodeId}";
		NetDebug::trace("Running a query = $query");	
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	
		
				
		$query = "UPDATE {$prefix}_npc_conversations SET text = '{$conversationText}' WHERE conversation_id = {$conversationId}";
		NetDebug::trace("Running a query = $query");	
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);	
		

		return new returnData(0, TRUE);
		
	}	
	
	
	/**
     * Delete a specific conversation and related node
     *
     * @param integer $gameId The game identifier
     * @param integer $conversationId The conversation identifier
	 *     
     * @return returnData
     * @returns a returnData object
     * @see returnData
     */	
     
	public function deleteConversationWithNode($gameId, $conversationId)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT node_id FROM {$prefix}_npc_conversations WHERE conversation_id = {$conversationId} LIMIT 1";
		NetDebug::trace("Running a query = $query");	
		$nodeIdRs = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);			
		$nodeIdObject = @mysql_fetch_object($nodeIdRs);
		if (!$nodeIdObject) return new returnData(2, NULL, "No such conversation");			
		$nodeId = $nodeIdObject->node_id;

		Nodes::deleteNode($gameId, $nodeId);
		
		$query = "DELETE FROM {$prefix}_npc_conversations WHERE conversation_id = {$conversationId}";
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) return new returnData(0);
		else return new returnData(2, NULL, 'invalid conversation id');
	}	

	
}