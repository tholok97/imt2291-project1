#!/bin/bash -x

echo "create database imt2291_project1_db" | sudo docker exec -i imt2291project1_db_1 mysql -uroot -psecret923
docker exec -i imt2291project1_db_1 mysql -uroot -psecret923 --database=imt2291_project1_db < docs/export_lowercase.sql
