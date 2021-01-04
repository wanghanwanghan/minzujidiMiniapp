#!/bin/bash

git pull

rm -f ./Log/*log* ./Static/Log/*

php easyswoole restart produce
