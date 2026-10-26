const configuredApiAddress = import.meta.env.VITE_API_BASE_URL?.trim();
const baseApiAddress = (configuredApiAddress || "https://qureshiarham.be/WM/fmv/Api/")
    .replace(/\/?$/, "/");

let defaultOptions = {
    method: "POST",
    mode: "cors",
    cache: "no-cache",
    credentials: "omit"
};

async function apiCall(endpoint, options = {}) {
    try {
        const url = baseApiAddress + endpoint;
        const config = {
            ...defaultOptions,
            ...options
        };

        if (config.body && typeof config.body === "object") {
            config.body = JSON.stringify(config.body);
            config.headers = {
                "Content-Type": "application/json",
                ...config.headers
            };
        }

        const response = await fetch(url, config);
        const responseData = await response.json();

        if (!response.ok) {
            throw new Error(responseData.message || responseData.error || `HTTP error! status: ${response.status}`);
        }

        return responseData;
    } catch (error) {
        console.error("API call failed:", error);
        throw error;
    }
}

async function apiPost(endpoint, data) {
    return await apiCall(endpoint, {
        method: "POST",
        body: data
    });
}

async function apiGet(endpoint) {
    return await apiCall(endpoint, {
        method: "GET",
        body: null
    });
}

async function apiPut(endpoint, data) {
    return await apiCall(endpoint, {
        method: "PUT",
        body: data
    });
}

async function apiDelete(endpoint) {
    return await apiCall(endpoint, {
        method: "DELETE"
    });
}

export {
    apiPost,
    apiGet,
    apiPut,
    apiDelete,
    baseApiAddress
};
