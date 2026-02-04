<script setup>
import { ref, computed } from 'vue'

// Sample data
const sampleData = {
    "invoice": {
        "number": "0001",
        "date": "2/3/2026",
        "currency": "USD",
        "total": 2880,
        "subtotal": 2880,
        "tax": 0,
        "paymentTerms": "30",
        "issuer": {
            "name": "3tone LLC",
            "address": "328 Argyle Rd\n11218 Brooklyn, NY",
            "email": "",
            "company": ""
        },
        "customer": {
            "name": "FWD People",
            "company": "",
            "address": "195 Montague St\nSuite 1409\n11201 Brooklyn",
            "email": ""
        }
    },
    "items": [
        {
            "description": "Clarus Website",
            "duration": 135600,
            "rate": 160,
            "amount": 1120,
            "project": "Clarus Website",
            "activity": "HTML/CSS/JS Coding",
            "date": "1/9/2026",
            "user": "Matt Lima"
        },
        {
            "description": "Zoetis Imagyst Calculator",
            "duration": 54600,
            "rate": 160,
            "amount": 960,
            "project": "Zoetis Imagyst Calculator",
            "activity": "HTML/CSS/JS Coding",
            "date": "1/15/2026",
            "user": "Gustavo Elizalde"
        },
        {
            "description": "American Regent Synoglide Email Template",
            "duration": 53940,
            "rate": 160,
            "amount": 160,
            "project": "American Regent Synoglide Email Template",
            "activity": "HTML/CSS/JS Coding",
            "date": "1/20/2026",
            "user": "Matt Lima"
        },
        {
            "description": "Guardant Liquid PDF to HTML",
            "duration": 41520,
            "rate": 160,
            "amount": 640,
            "project": "Guardant Liquid PDF to HTML",
            "activity": "HTML/CSS/JS Coding",
            "date": "1/27/2026",
            "user": "Gustavo Elizalde"
        }
    ]
}

// Internal state
const jsonInput = ref('')
const parseError = ref('')
const invoiceData = ref(null)
const subject = ref('') // Manually editable field
const dueDate = ref('') // Editable due date
const paymentTerms = ref('') // Editable payment terms (e.g., "30 days")
const editingAmountIndex = ref(null) // Track which item amount is being edited

// Computed properties for template access
const invoice = computed(() => invoiceData.value?.invoice || null)
const items = computed(() => invoiceData.value?.items || [])
const hasData = computed(() => invoice.value !== null && items.value.length > 0)

// Calculate due date by adding paymentTerms to invoice date
// If paymentTerms is 30, add 1 month instead of 30 days
const calculateDueDate = (invoiceDate, paymentTerms) => {
    if (!invoiceDate || !paymentTerms) return ''

    // Parse date (assuming M/D/YYYY format)
    const dateParts = invoiceDate.split('/')
    if (dateParts.length !== 3) return invoiceDate

    const month = parseInt(dateParts[0], 10)
    const day = parseInt(dateParts[1], 10)
    const year = parseInt(dateParts[2], 10)

    const issueDate = new Date(year, month - 1, day)
    const days = parseInt(paymentTerms, 10) || 0

    const due = new Date(issueDate)
    
    if (days === 30) {
        // Add 1 month instead of 30 days
        due.setMonth(due.getMonth() + 1)
    } else {
        due.setDate(due.getDate() + days)
    }

    // Format back to M/D/YYYY
    return `${due.getMonth() + 1}/${due.getDate()}/${due.getFullYear()}`
}

// Roll up items by project
const rollUpByProject = (items) => {
    const projectMap = new Map()

    for (const item of items) {
        const projectKey = item.project || item.description

        if (!projectMap.has(projectKey)) {
            projectMap.set(projectKey, {
                project: projectKey,
                descriptions: new Set(),
                amount: 0,
                rate: item.rate || 0,
                duration: 0,
                dates: [],
                user: item.user || ''
            })
        }

        const entry = projectMap.get(projectKey)

        // Sum amounts and duration
        entry.amount += parseFloat(item.amount) || 0
        entry.duration += parseFloat(item.duration) || 0

        // Collect unique descriptions (excluding generic activity names that match the activity field)
        if (item.description) {
            entry.descriptions.add(item.description)
        }

        // Collect dates for range
        if (item.date) {
            entry.dates.push(item.date)
        }
    }

    // Convert map to array and format
    return Array.from(projectMap.values()).map(entry => {
        // Build subtitle from unique descriptions (deduped, concatenated)
        const subtitle = Array.from(entry.descriptions).join(', ')

        // Calculate date range
        let dateRange = ''
        if (entry.dates.length > 0) {
            const parsedDates = entry.dates.map(d => {
                const parts = d.split('/')
                return new Date(parseInt(parts[2]), parseInt(parts[0]) - 1, parseInt(parts[1]))
            }).sort((a, b) => a - b)

            const earliest = parsedDates[0]
            const latest = parsedDates[parsedDates.length - 1]

            const formatDate = (d) => `${d.getMonth() + 1}/${d.getDate()}/${d.getFullYear()}`

            if (earliest.getTime() === latest.getTime()) {
                dateRange = formatDate(earliest)
            } else {
                dateRange = `${formatDate(earliest)} - ${formatDate(latest)}`
            }
        }

        return {
            description: entry.project,  // Project name on top
            activity: subtitle,          // Concatenated descriptions below
            amount: entry.amount,
            rate: entry.rate,
            duration: entry.duration,
            project: entry.project,
            date: dateRange,
            user: entry.user
        }
    })
}

const parseJson = () => {
    parseError.value = ''

    if (!jsonInput.value.trim()) {
        invoiceData.value = null
        return
    }

    try {
        const parsed = JSON.parse(jsonInput.value)

        // Validate structure
        if (!parsed.invoice || !parsed.items) {
            throw new Error('JSON must contain "invoice" and "items" properties')
        }
        if (!parsed.invoice.issuer || !parsed.invoice.customer) {
            throw new Error('Invoice must contain "issuer" and "customer" objects')
        }
        if (!Array.isArray(parsed.items)) {
            throw new Error('"items" must be an array')
        }

        // Roll up items by project
        parsed.items = rollUpByProject(parsed.items)

        // Recalculate totals from rolled-up items
        const subtotal = parsed.items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
        parsed.invoice.subtotal = subtotal
        parsed.invoice.total = subtotal + (parseFloat(parsed.invoice.tax) || 0)

        invoiceData.value = parsed
        
        // Calculate initial due date
        dueDate.value = calculateDueDate(parsed.invoice.date, parsed.invoice.paymentTerms)
        
        // Set payment terms with "days" appended
        const terms = parsed.invoice.paymentTerms || ''
        paymentTerms.value = terms ? `${terms} days` : ''
    } catch (e) {
        parseError.value = e.message
        invoiceData.value = null
    }
}

const loadSample = () => {
    jsonInput.value = JSON.stringify(sampleData, null, 4)
    parseJson()
}

const resetInvoice = () => {
    jsonInput.value = ''
    invoiceData.value = null
    parseError.value = ''
    subject.value = ''
    dueDate.value = ''
    paymentTerms.value = ''
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: invoice.value?.currency || 'USD'
    }).format(value)
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    return dateString
}

// Item management
const addItem = () => {
    if (!invoiceData.value) return
    invoiceData.value.items.push({
        description: '',
        duration: 0,
        rate: 160,
        amount: 0,
        project: '',
        activity: '',
        date: '',
        user: ''
    })
}

const removeItem = (index) => {
    if (!invoiceData.value) return
    invoiceData.value.items.splice(index, 1)
    recalculateTotal()
}

const recalculateTotal = () => {
    if (!invoiceData.value) return
    const subtotal = invoiceData.value.items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
    invoiceData.value.invoice.subtotal = subtotal
    invoiceData.value.invoice.total = subtotal + (parseFloat(invoiceData.value.invoice.tax) || 0)
}
</script>

<template>
    <!-- Empty State: JSON Input -->
    <div v-if="!hasData" class="empty-state">
        <div class="input-container">
            <h2>Paste Invoice JSON</h2>
            <p class="instructions">Paste your invoice JSON data below to preview the invoice.</p>
            <textarea v-model="jsonInput" @input="parseJson" placeholder='{"invoice": {...}, "items": [...]}'
                class="json-input"></textarea>
            <div class="button-row">
                <button @click="loadSample" class="sample-btn">Load Sample</button>
            </div>
            <p v-if="parseError" class="error-message">{{ parseError }}</p>
        </div>
    </div>

    <!-- Invoice Display -->
    <div v-else class="invoice-wrapper">
        <div class="toolbar">
            <button @click="resetInvoice" class="reset-btn">← New Invoice</button>
        </div>
        <div class="invoice-container">
            <header class="header">
                <div class="logo-section">
                    <!-- Real Logo -->
                    <div class="logo">
                        <img src="../assets/logo.png" alt="3TONE Logo" class="logo-img" />
                    </div>
                </div>

                <div class="invoice-title-section">
                    <h1>INVOICE</h1>
                </div>
            </header>

            <section class="details-row">
                <div class="meta-info">
                    <div class="meta-row">
                        <span class="label">Invoice ID</span>
                        <span class="value">
                            <input type="text" v-model="invoiceData.invoice.number"
                                class="editable-input editable-input--bold" />
                        </span>
                    </div>
                    <div class="meta-row">
                        <span class="label">Issue Date</span>
                        <span class="value">
                            <input type="text" v-model="invoiceData.invoice.date" class="editable-input"
                                placeholder="M/D/YYYY" />
                        </span>
                    </div>
                    <div class="meta-row">
                        <span class="label">Due Date</span>
                        <span class="value">
                            <input type="text" v-model="dueDate" class="editable-input"
                                placeholder="M/D/YYYY" />
                        </span>
                    </div>
                    <div class="meta-row">
                        <span class="label">Terms</span>
                        <span class="value">
                            <input type="text" v-model="paymentTerms" class="editable-input"
                                placeholder="30 days" />
                        </span>
                    </div>
                    <div class="meta-row">
                        <span class="label">Subject</span>
                        <span class="value">
                            <input type="text" v-model="subject" class="editable-input"
                                placeholder="Enter subject..." />
                        </span>
                    </div>
                </div>

                <div class="addresses-column">
                    <div class="address-section">
                        <div class="address-label">From</div>
                        <div class="address-content">
                            <strong>{{ invoice.issuer.name?.trim() }}</strong><br>
                            {{ invoice.issuer.address?.trim() }}
                        </div>
                    </div>
                    <div class="address-section">
                        <div class="address-label">Invoice For</div>
                        <div class="address-content">
                            <strong>{{ invoice.customer.name?.trim() }}</strong><br>
                            {{ invoice.customer.address?.trim() }}<br>
                            <template v-if="invoice.customer.contact?.trim()">{{ invoice.customer.contact?.trim() }}<br></template>
                            <template v-if="invoice.customer.email?.trim()">{{ invoice.customer.email?.trim() }}</template>
                        </div>
                    </div>
                </div>
            </section>

            <section class="items-table">
                <div class="table-header">
                    <div class="col-desc">Description</div>
                    <div class="col-amount">Amount</div>
                    <div class="col-actions"></div>
                </div>
                <div v-for="(item, index) in items" :key="index" class="table-row">
                    <div class="col-desc">
                        <input type="text" v-model="item.description"
                            class="editable-input editable-input--full item-title-input" placeholder="Description" />
                        <input type="text" v-model="item.activity"
                            class="editable-input editable-input--full item-subtitle-input" />
                    </div>
                    <div class="col-amount">
                        <span v-if="editingAmountIndex !== index" @click="editingAmountIndex = index"
                            class="amount-display">{{ formatCurrency(item.amount) }}</span>
                        <input v-else type="number" v-model.number="item.amount" @input="recalculateTotal"
                            @blur="editingAmountIndex = null" @keyup.enter="editingAmountIndex = null"
                            class="editable-input editable-input--number" placeholder="0" ref="amountInput" autofocus />
                    </div>
                    <div class="col-actions">
                        <button @click="removeItem(index)" class="remove-btn" title="Remove item">×</button>
                    </div>
                </div>
                <div class="add-item-row">
                    <button @click="addItem" class="add-btn">+ Add Item</button>
                </div>
            </section>

            <section class="totals">
                <div class="total-row large">
                    <span>Amount Due</span>
                    <span>{{ formatCurrency(invoice.total) }}</span>
                </div>
            </section>

            <section class="notes">
                <h3>Notes</h3>
                <p>Please make checks payable to {{ invoice.issuer.name }}.</p>
            </section>
        </div>
    </div>
</template>

<style scoped>
/* Empty State Styles */
.empty-state {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: #f5f5f5;
    padding: 20px;
}

.input-container {
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    width: 100%;
}

.input-container h2 {
    margin: 0 0 10px 0;
    font-size: 24px;
    color: #333;
}

.instructions {
    color: #666;
    margin-bottom: 20px;
    font-size: 14px;
}

.json-input {
    width: 100%;
    height: 300px;
    padding: 15px;
    font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
    font-size: 12px;
    border: 2px solid #ddd;
    border-radius: 6px;
    resize: vertical;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.json-input:focus {
    outline: none;
    border-color: #666;
}

.button-row {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.sample-btn {
    padding: 10px 20px;
    font-size: 13px;
    background: #333;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s;
}

.sample-btn:hover {
    background: #555;
}

.error-message {
    color: #d32f2f;
    margin-top: 10px;
    font-size: 13px;
    padding: 10px;
    background: #ffebee;
    border-radius: 4px;
}

/* Toolbar Styles */
.invoice-wrapper {
    background: #f5f5f5;
    min-height: 100vh;
}

.toolbar {
    padding: 15px 50px;
    background: #fff;
    border-bottom: 1px solid #ddd;
    position: sticky;
    top: 0;
    z-index: 100;
}

.reset-btn {
    padding: 8px 16px;
    font-size: 13px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.reset-btn:hover {
    background: #e0e0e0;
}

/* Invoice Container Styles */
.invoice-container {
    width: 770px;
    max-width: 770px;
    margin: 0 auto;
    padding: 50px;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    color: #333;
    background: white;
    min-height: 100vh;
}

.header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 60px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logo-img {
    max-width: 140px;
    /* Reduced by 30% from 200px */
    height: auto;
    display: block;
}

.invoice-title-section {
    text-align: right;
}

h1 {
    font-size: 20px;
    font-weight: 500;
    margin: 0;
    text-transform: uppercase;
    color: #333;
}

.addresses-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.address-section {
    display: flex;
    gap: 20px;
    font-size: 12px;
}

.address-label {
    width: 80px;
    text-align: left;
    color: #888;
    flex-shrink: 0;
}

.address-content {
    text-align: left;
    white-space: pre-wrap;
    line-height: 1.4;
}

.label {
    color: #999;
    font-size: 11px;
}

.details-row {
    display: flex;
    justify-content: space-between;
    gap: 40px;
    margin-bottom: 50px;
}

.meta-info {
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
}

.meta-row {
    display: flex;
    gap: 20px;
}

.meta-row .label {
    width: 80px;
    flex-shrink: 0;
}

.meta-row .value {
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
    flex: 1;
}

/* Editable Input Styles */
.editable-input {
    font-size: 12px;
    font-weight: 500;
    font-family: inherit;
    border: none;
    border-bottom: 1px dashed transparent;
    background: transparent;
    padding: 2px 4px;
    margin: -2px -4px;
    width: 100%;
    min-width: 0;
    transition: all 0.2s;
}

.editable-input:hover {
    border-bottom-color: #ccc;
    background: #fafafa;
}

.editable-input:focus {
    outline: none;
    border-bottom-color: #666;
    background: #fff;
}

.editable-input--bold {
    font-weight: 600;
}

.editable-input--small {
    min-width: 40px;
    width: 40px;
    text-align: center;
}

.editable-input::placeholder {
    color: #bbb;
    font-style: italic;
}

.items-table {
    margin-bottom: 40px;
}

.table-header {
    display: flex;
    border-bottom: 2px solid #ddd;
    padding-bottom: 5px;
    margin-bottom: 10px;
    font-weight: bold;
    font-size: 11px;
    color: #555;
    text-transform: uppercase;
    gap: 10px;
}

.table-row {
    display: flex;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    gap: 10px;
    align-items: flex-start;
}

.table-row:hover {
    background: #fafafa;
}

.col-desc {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.col-amount {
    width: 120px;
    text-align: right;
    font-weight: bold;
}

.amount-display {
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 3px;
    transition: background 0.2s;
}

.amount-display:hover {
    background: #f0f0f0;
}

.col-actions {
    width: 30px;
    text-align: center;
}

.editable-input--full {
    width: 100%;
    min-width: unset;
}

.item-title-input {
    font-weight: 500;
}

.item-subtitle-input {
    font-size: 11px;
    color: #666;
}

.editable-input--number {
    width: 100%;
    min-width: unset;
    text-align: right;
}

/* Hide spinner buttons on number inputs */
.editable-input--number::-webkit-outer-spin-button,
.editable-input--number::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.editable-input--number[type=number] {
    -moz-appearance: textfield;
}

.remove-btn {
    width: 24px;
    height: 24px;
    border: none;
    background: transparent;
    color: #ccc;
    font-size: 18px;
    cursor: pointer;
    border-radius: 4px;
    line-height: 1;
    transition: all 0.2s;
}

.remove-btn:hover {
    background: #fee;
    color: #d32f2f;
}

.add-item-row {
    padding: 15px 0;
}

.add-btn {
    padding: 8px 16px;
    font-size: 12px;
    background: transparent;
    border: 1px dashed #ccc;
    border-radius: 4px;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.add-btn:hover {
    border-color: #999;
    background: #fafafa;
    color: #333;
}

.item-title {
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 4px;
    white-space: pre-wrap;
}

.item-subtitle {
    font-size: 11px;
    color: #666;
}

.totals {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 60px;
}

.total-row.large {
    display: flex;
    gap: 40px;
    font-size: 16px;
    font-weight: bold;
}

.notes h3 {
    font-size: 12px;
    margin-bottom: 5px;
    color: #333;
}

.notes p {
    font-size: 13px;
    color: #555;
}

.address-block {
    white-space: pre-wrap;
    line-height: 1.4;
}

strong {
    font-weight: 600;
}

/* Print Styles */
@media print {
    .toolbar {
        display: none !important;
    }

    .add-item-row {
        display: none !important;
    }

    .col-actions {
        display: none !important;
    }

    .remove-btn {
        display: none !important;
    }

    .invoice-wrapper {
        background: white;
    }

    .invoice-container {
        padding: 0;
        margin: 0;
        width: 670px;
        max-width: 670px;
    }

    .table-row:hover {
        background: transparent;
    }

    .editable-input {
        border: none !important;
        background: transparent !important;
        padding: 0;
        margin: 0;
    }

    .editable-input:hover,
    .editable-input:focus {
        border: none !important;
        background: transparent !important;
    }

    .amount-display {
        cursor: default;
    }

    .amount-display:hover {
        background: transparent;
    }
}
</style>
