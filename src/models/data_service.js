export class DataService {
    /**
     * @constructor
     * 
     * @param {boolean} useSession
     * True = sessionStorage
     * False = localStorage (default)
     */
    constructor(data_source, storage_key, use_session=false){
        /**
         * @private {boolean} _storageType
         */
        this._storageType = use_session ? "session" : "local";
        /**
         * @private {string}
         */
        this._storageKey = storage_key;
        /**
         * @private {string}
         */
        this._dataSource = data_source;
        /**
         * @private {object}
         */
        this._data = {};
        /**
         * When instantiated, check local / session storage for data
         * - If data doesnt exist, load defaults
         */
    }
    /**
     * Loads settings data from local storage or a remote JSON file.
     *
     * This method first checks if a JSON string is stored in local storage under the key "settings".
     * If found, it parses the string and assigns the resulting object to the private `_data` variable.
     * If no data is found in local storage, it fetches a JSON file from the server
     * and assigns the parsed response to `_data`.
     *
     * @async
     * @memberof DataService
     * @returns {Promise<object>} A promise that resolves when the data has been loaded.
     * @throws {Error} Throws an error if there is an issue with parsing the JSON from local storage
     * or if the fetch request fails.
     */
    async load() {
        /**
         * Check local storage
         * - Determine session or local
         * - Try and look for data
         */
        try {
            // Determine storage type and check for data
            const localData = (this._storageType === "local") ? localStorage.getItem(this._storageKey) : sessionStorage.getItem(this._storageKey);
            /**
             * Check if local data
             * Else perform fetch
             */
            if(localData){
                // Load local on success
                this._data = JSON.parse(localData);
                console.log(this._data);
            } else {
                // Fetch and load
                const response = await fetch(this._dataSource);
                // On Failure
                if(!response.ok){
                    throw new Error(`HTTP Error: status ${response.status}`);
                }
                // Await and load
                this._data = await response.json();
            }
            return this._data;
        } catch (err){
            // Both methods failed
            console.error(err);
        }
    }
    /**
     * Save data to session / local storage
     * @memberof DataService
     * @param {object} data
     * @property {object} this._data
     * @returns {void}
     */
    save(data){
        /**
         * TODO: Validate Data
         * - Check matching keys
         */
        // Save data to private variable
        this._data = data;
        /**
         * Perform try / catch errors
         */
        try {
            // Save into session / local storage
            if(this._storageType === "local"){
                localStorage.setItem(this._storageKey, JSON.stringify(data));
            } else if (this._storageType === "session"){
                sessionStorage.setItem(this._storageKey, JSON.stringify(data));
            }
        } catch (err){
            console.error("Unable to save data!", err);
        }
    }

}