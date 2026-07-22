# Chain Adapters

Chain adapters isolate coin-specific behavior from the shared explorer core.

The adapter interface exposes:

- Chain identity and display name
- RPC client construction
- Feature flags
- Access to the underlying chain configuration

The first reference implementation is `MuntAdapter`, which reads its configuration from `backend/config/chains/munt.php`.