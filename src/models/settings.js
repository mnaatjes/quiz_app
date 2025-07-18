/**
 * @class Settings
 * @memberof QuizApp
 * @version 1.0
 * @since 1.0: 
 *      - Created
 */
export class Settings {
    /**
     * @constant {string} STORAGE_KEY
     */
    static STORAGE_KEY = 'settingsData';
    /**
     * @constructor
     * 
     * @param {boolean} useSession
     * True = sessionStorage
     * False = localStorage (default)
     */
    constructor(useSession=false){
        /**
         * @private {boolean} _storageType
         */
        this._storageType = useSession;
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
     * @memberof MyClass
     * @returns {Promise<void>} A promise that resolves when the data has been loaded.
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
            const localData = (this._storageType === false) ? localStorage.getItem(this.STORAGE_KEY) : sessionStorage.getItem(this.STORAGE_KEY);
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
                const response = await fetch('src/data/settings.json');
                // On Failure
                if(!response.ok){
                    throw new Error(`HTTP Error: status ${response.status}`);
                }
                // Await and load
                this._data = await response.json();
                console.log(this._data);
            }
        } catch (err){
            // Both methods failed
            console.error(err);
        }
    }
}