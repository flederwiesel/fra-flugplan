#!/bin/bash

# drop/re-create database
initdb

query < <(
	echo 'USE `fra-flugplan`;'
	cat ../sql/data/countries.sql \
		../sql/data/airlines.sql \
		../sql/data/airports.sql \
		../sql/data/models.sql
)
