<?php

namespace ValueObjects;

use ValueObjects\DeterbTupleStore;

/**
 * Used to represent one package data to apply in DETERB table.
 * The package is a set of DeterbTupleStore.
 * 
 * It is compounds of 3 sets of tuples, insert, update and delete in SQL format.
 * 
 * May of 2017
 *
 * @author andre
 *
 */
class DeterbTableStore {

	private $insertTuples, $updateTuples, $deleteTuples;
	
	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
	
	public function __set($property, $value) {
		if (property_exists($this, $property)) {
			$this->$property = $value;
		}
	
		return $this;
	}
	
	function __construct($jsonResponse=null) {
		if(isset($jsonResponse)) {
			$this->insertTuples = $jsonResponse->inserts;
			$this->updateTuples = $jsonResponse->updates;
			$this->deleteTuples = $jsonResponse->deletes;
		}
	}
	
	/**
	 * Makes the SQL script to create the table.
	 * @return string, The SQL script to create table on database.
	 */
	public function getSQLToCreateTableStore() {
		$sql="";
		$sql.="CREATE TABLE public.deterb_sync_cra ".
		$sql.="( ".
		$sql.="gid bigint NOT NULL, ".
		$sql.="classname character varying(254), ".
		$sql.="areatotalkm numeric, ".
		$sql.="areamunkm double precision, ".
		$sql.="areauckm double precision, ".
		$sql.="date date, ".
		$sql.="uf character varying(2), ".
		$sql.="county text, ".
		$sql.="uc character varying, ".
		$sql.="satellite character varying(13), ".
		$sql.="sensor character varying(10), ".
		$sql.="lot character varying(254), ".
		$sql.="orbitpoint character varying(10), ".
		$sql.="quadrant character varying(5), ".
		$sql.="geom geometry, ".
		$sql.="CONSTRAINT deterb_sync_cra_pk PRIMARY KEY (gid) ".
		$sql.=") ".
		$sql.="WITH ( ".
		$sql.="OIDS=FALSE ".
		$sql.="); ".
		$sql.="ALTER TABLE public.deterb_sync_cra ".
		$sql.="OWNER TO postgres; ".
		$sql.="CREATE INDEX deterb_sync_cra_geom_index ".
		$sql.="ON public.deterb_sync_cra ".
		$sql.="USING gist ".
		$sql.="(geom);";
		
		return $sql;
	}
	
	/**
	 * Makes a SQL script to insert rows on table.
	 * @param integer $numRows, allow read the number of rows that will be inserted.
	 * @return <boolean, string>, The SQL script to insert rows or false otherwise.
	 */
	public function toSQLInsert(&$numRows) {
		$sql=false;
		$numRows=0;
		if(isset($this->insertTuples) && $this->insertTuples->length) {
			$index = $this->insertTuples->length;
			for ($i = 0; $i < $index; $i++) {
				$numRows++;
				$tuple = new DeterbTupleStore($this->insertTuples[$i]);
				$sql .= $tuple->toSQLInsert() . ";";
			}
		}
		return $sql;
	}
	
	/**
	 * Makes a SQL script to update rows from table.
	 * @param integer $numRows, allow read the number of rows that will be updated.
	 * @return <boolean, string>, The SQL script to update rows or false otherwise.
	 */
	public function toSQLUpdate(&$numRows) {
		$sql=false;
		$numRows=0;
		if(isset($this->updateTuples) && $this->updateTuples->length) {
			$index = $this->updateTuples->length;
			for ($i = 0; $i < $index; $i++) {
				$numRows++;
				$tuple = new DeterbTupleStore($this->updateTuples[$i]);
				$sql .= $tuple->toSQLUpdate() . ";";
			}
		}
		return $sql;
	}
	
	/**
	 * Makes a SQL script to delete rows from table.
	 * @param integer $numRows, allow read the number of ids that will be removed.
	 * @return <boolean, string>, The SQL script to delete rows or false otherwise.
	 */
	public function toSQLDelete(&$numRows) {
		$sql=false;
		$numRows=0;
		if(isset($this->deleteTuples) && $this->deleteTuples->length) {
			$index = $this->deleteTuples->length;
			for ($i = 0; $i < $index; $i++) {
				$numRows++;
				$tuple = new DeterbTupleStore($this->deleteTuples[$i]);
				$sql .= $tuple->toSQLDelete() . ";";
			}
		}
		return $sql;
	}
	
}