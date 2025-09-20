import axios from "axios";

const client = axios.create({
  baseURL: "/api/v1",
  withCredentials: true,
  headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
});

async function csrf() {
  await axios.get("/sanctum/csrf-cookie", { withCredentials: true });
}

export async function apiGet(url, params) {
  try {
    const { data } = await client.get(url, { params });
    return data; // { ok, data, meta }
  } catch (error) {
    console.error(`API GET ${url} failed:`, error.response?.data || error.message);
    throw error;
  }
}

export async function apiPost(url, body, config) {
  try {
    await csrf();
    const { data } = await client.post(url, body, config);
    return data;
  } catch (error) {
    console.error(`API POST ${url} failed:`, error.response?.data || error.message);
    throw error;
  }
}

export async function apiPatch(url, body) {
  try {
    await csrf();
    const { data } = await client.patch(url, body);
    return data;
  } catch (error) {
    console.error(`API PATCH ${url} failed:`, error.response?.data || error.message);
    throw error;
  }
}

export async function apiDelete(url) {
  try {
    await csrf();
    const { data } = await client.delete(url);
    return data;
  } catch (error) {
    console.error(`API DELETE ${url} failed:`, error.response?.data || error.message);
    throw error;
  }
}
