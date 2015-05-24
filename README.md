# Uber-Coding-Challenge

* This application is hosted on:
* API documentation can be viewed here: [Uber Coding Challenge Wiki](https://github.com/anna-maria/Uber-Coding-Challenge/wiki)
* This solution focuses on **back-end**. PHP (2.5 years of experience), Slim Framework (no experience), and PostgreSQL (2.5 years of MySQL experience - no experience with PostgreSQL) are used and a very basic front-end is included. 

A service that shows where movies were filmed in SF on a map. Users should be able to filter the view using autocompletion.
At this time, the front-end is very minimal and markers on the map will only appear when a movie is selected from the drop down choices. Autocomplete and marker lat/lng information is retrieved from the API back-end (using JSON).

###Architectural Choices
I choose to create a RESTful API using PHP and Slim Framework with one resources: movie. All API endpoints interact with the database.

For the database structure, I chose to divide the data into three tables: movie, location, and movie_location. The tables are structured in this manner to keep the database normalized (due to the many to many movie_location relation table):
```
movie: id, name, deleted_at
location: id, place, lat, lng
movie_location: id, movie_id, location_id
```
A populateDB script is used to call the DataSF api and loop through all results (calling Google Maps Geocode API to retrieve latitude and longitude for all unique locations) to properly fill my three tables. As a future improvement, we can note that some locations do not return a valid lat/lng and this case should not be inserted into the movie_location table. 

Testing of the API is in the form of basic integration testing. It tests the API with a POST, GET, GET (by specific ID), PUT, and DELETE sequence. 

###Future improvements
Future improvements (limited by timing) can included creating a second resource: location. This would allow us to fully treat movies and location seperately and allow access to relations between the two in this (sample) endpoint manner:
```
GET /movies/:id/location/:id 
```
