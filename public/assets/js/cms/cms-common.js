/**
 * CMS Common Utilities
 * Provides common functions for CMS operations like deleteRecord
 */

// Ensure baseURL is available
if (typeof baseURL === 'undefined') {
    console.warn('[cms-common.js] baseURL is not defined. Some functions may not work correctly.');
}

/**
 * Delete a record from a table
 * This is a wrapper/alias for deleteTableRecord from common.js
 * 
 * @param {number|string} rowid - The ID of the record to delete
 * @param {number} deleteStatus - The delete status (0 or 1)
 * @param {string} tableName - The name of the table
 * @param {string} targetTable - The target table selector for DataTable reload
 * @param {string} col - Optional column name
 */
function deleteRecord(rowid, deleteStatus, tableName, targetTable, col = "") {
    // Use the deleteTableRecord function from common.js if available
    if (typeof deleteTableRecord === 'function') {
        deleteTableRecord(rowid, deleteStatus, tableName, targetTable, col);
    } else {
        console.error('[cms-common.js] deleteTableRecord function not found. Make sure common.js is loaded before cms-common.js');
    }
}

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        deleteRecord: deleteRecord
    };
}

