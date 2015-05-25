# Uber-Coding-Challenge

* This application is hosted on:
* API documentation can be viewed here: [Uber Coding Challenge Wiki](https://github.com/anna-maria/Uber-Coding-Challenge/wiki)
* This solution focuses on **back-end**. PHP (experience: 2 years +), Slim Framework (experience: none), and PostgreSQL (experience: 2 years + of MySQL experience - no experience with PostgreSQL) are used and a very basic front-end is included. 

This project involved creating a service that shows where movies were filmed in San Francisco on a map. Users should be able to filter the view using autocompletion. At this time, the front-end is very minimal and markers on the map will only appear when a movie is selected from the drop down choices. Autocomplete and marker lat/lng information is retrieved from the API back-end in the form of a JSON response.

###Architectural Choices
I chose to create a RESTful API (with one resources: movies) using PHP and Slim Framework. All API endpoints interact with the database and accept/return JSON. Proper HTTP status codes are returned regardless of response status. 

For the database structure, I chose to divide the data into three tables: movie, location, and movie_location. The tables are structured in this manner to keep the database normalized (due to the many to many movie_location relation table):
```
movie: id, name, deleted_at
location: id, place, lat, lng
movie_location: id, movie_id, location_id
```
A populateDB script is used to call the DataSF api and loop through all results (calling Google Maps Geocode API to retrieve latitude and longitude for all unique locations) to properly fill these three tables. Testing of the API is in the form of basic integration testing. It tests the API with a POST, GET, GET (by specific ID), PUT, and DELETE sequence. 

###Future improvements
Future improvements (limited by timing) could included creating a second resource: location. This would allow the movies and locations resources to be treated seperately and allow access to relations between the two in this (sample) endpoint manner:
```
GET /movies/:id/location/:id 
```
Due to the static nature of the movie/location data, the API could also be improved by adding caching to each endpoint. This would be most useful for high GET request traffic. For the scope of this project, caching was left out. Testing could be improved by adding unit tests throughout the application for each function. As for database improvements: we can note that some locations do not return a valid lat/lng and this case should not be inserted into the movie_location table.
