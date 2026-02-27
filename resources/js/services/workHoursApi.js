import axios from 'axios';

export const fetchAvgWorkHours = async (endpointUrl, params = {}, signal) => {
  if (typeof endpointUrl !== 'string' || endpointUrl.trim() === '') {
    throw new Error('Avg Work Hours endpoint is missing.');
  }
  const { data } = await axios.get(endpointUrl, { signal, params });
  return data;
};

export const fetchMonthlyWorkHours = async (endpointUrl, params = {}, signal) => {
  if (typeof endpointUrl !== 'string' || endpointUrl.trim() === '') {
    throw new Error('Monthly Work Hours endpoint is missing.');
  }
  const { data } = await axios.get(endpointUrl, { signal, params });
  return data;
};

