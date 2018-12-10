import unittest
from import_helpers import seed_io
from import_helpers.location_container import LocationContainer


class LocationContainerTests(unittest.TestCase):
	def test_locations_near(self):
		locations = seed_io.load_seed_data_from('location')
		container = LocationContainer(locations)

		# smoke test a couple methods.
		windsor = {
			'latitude': 42.3,
			'longitude': -83
		}
		index = container.get_bucket_index_from_latitude(windsor['latitude'])
		self.assertTrue(isinstance(index, int))
		results = list(container.locations_near(windsor['longitude'], windsor['latitude'], 0.5))
		self.assertTrue(len(results) > 0)
		container.insert(windsor)
		new_results = list(container.locations_near(windsor['longitude'], windsor['latitude'], 0.5))
		self.assertTrue(len(new_results) == len(results) + 1)
		

if __name__ == '__main__':
	unittest.main()