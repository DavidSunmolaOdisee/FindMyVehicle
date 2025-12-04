const baseApiAddress = "https://qureshiarham.be/WM/fmv/Api/";

let defaultOptions = {
    method: "POST",
    mode: "cors", 
    cache: "no-cache",
    credentials: "omit"
};

async function apiCall(endpoint, options = {}) {
    try {
        let url = baseApiAddress + endpoint;
        const config = {
            ...defaultOptions,
            ...options
        };

        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
            config.headers = {
                "Content-Type": "application/json",
                ...config.headers
            };
        }

        console.log("API Call:", url, config);

        const response = await fetch(url, config);
        const responseData = await response.json();
        
        if (!response.ok) {
            throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
        }
        
        return responseData;
    } catch (error) {
        console.log("Fout bij API call: ", error);
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