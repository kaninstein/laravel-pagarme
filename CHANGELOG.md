# Changelog

All notable changes to `laravel-pagarme` will be documented in this file.

## 1.1.0 - 2025-12-14

### Fixes
- Auto-generate `items[].code` when omitted to satisfy Pagar.me order validation
- Ensure tokenized credit card payments include a billing address (fallback to `customer.address` when available)
- Update test fixtures to use a valid CPF for sandbox validations

## 1.0.0 - 2025-12-13

### Initial Release

#### Core Features
- Support for Laravel 10, 11, and 12
- PHP 8.2, 8.3, and 8.4 compatibility
- Direct integration with Pagar.me API v5 (no official SDK dependency)
- Basic Auth authentication
- Type-safe DTOs for data structuring
- Comprehensive error handling with specific exceptions
- Automatic retry on temporary failures
- Configurable logging
- Pagination support
- Support for metadata on all main objects

#### API Resources
- **Customer Management**: create, read, update, delete, list
- **Order Management**: create, read, list, close
- **Charge Management**: get, list, retry, cancel, capture
- **Webhook Management**: create, read, update, delete, list
- **Card Management**: create, read, update, delete, list
- **BIN Lookup**: validate and get card information
- **Token Service**: secure card tokenization

#### Payment Methods
- ✅ Credit Card (with installments support)
- ✅ Debit Card
- ✅ PIX (with QR Code generation)
- ✅ Boleto (bank slip with fine and interest)
- ✅ Voucher (meal/food vouchers: VR, Alelo, Sodexo, Ticket, Pluxee)
- ✅ Cash
- ✅ SafetyPay
- ✅ Private Label

#### Advanced Features
- **SubMerchant Support**: Full support for Payment Facilitators (configurable via .env or per-order)
- **Brazilian Helpers**: PhoneDTO, AddressDTO with Brazilian-specific validations
- **ABECS Return Codes**: Complete mapping of 60+ standardized return codes with retry information
- **Transaction Declined Exception**: Detailed decline information with helper methods (isFraudRelated, isInsufficientFunds, etc.)

#### Testing & Simulators
- Complete test coverage with PHPUnit
- Test scenarios for all Pagar.me simulators:
  - Credit Card (11 test scenarios)
  - Debit Card (6 test scenarios)
  - PIX (5 test scenarios)
  - Boleto (5 test scenarios)
  - Voucher (7 test scenarios)
- All tests run against real Pagar.me sandbox API

#### Documentation
- Comprehensive README with examples
- TOKENIZATION_GUIDE.md - Complete tokenization guide
- CODIGOS_RETORNO.md - Full ABECS codes reference and test card numbers
- STRUCTURE.md - Package architecture and structure
- CONTRIBUTING.md - Contribution guidelines
