import { apiGet } from '@/api/client';
import type { City, Country } from '@/api/types';

export function getCountries(): Promise<Country[]> {
    return apiGet<Country[]>('/locations/country');
}

export function getCitiesByCountry(countryId: number): Promise<City[]> {
    return apiGet<City[]>(`/locations/country/${countryId}/cities`);
}
