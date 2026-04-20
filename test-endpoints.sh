#!/bin/bash

# Couleurs pour l'output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_URL="http://localhost:8000/api"
REGISTER_EMAIL="test$(date +%s)@test.com"
REGISTER_PASSWORD="Password123!"

echo -e "${BLUE}===============================================${NC}"
echo -e "${BLUE}     TEST DES ENDPOINTS CRITIQUES${NC}"
echo -e "${BLUE}===============================================${NC}\n"

# ============================================================================
# 1. TEST AUTHENTIFICATION
# ============================================================================
echo -e "${YELLOW}1️⃣  TEST AUTHENTIFICATION${NC}"
echo "-----------------------------------"

# Register
echo -e "${BLUE}[TEST] POST /api/auth/register${NC}"
REGISTER_RESPONSE=$(curl -s -X POST "$API_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"name\": \"Test User\",
    \"email\": \"$REGISTER_EMAIL\",
    \"password\": \"$REGISTER_PASSWORD\",
    \"password_confirmation\": \"$REGISTER_PASSWORD\",
    \"role\": \"client\"
  }")

echo "$REGISTER_RESPONSE" | jq '.' 2>/dev/null || echo "$REGISTER_RESPONSE"
REGISTER_STATUS=$(echo "$REGISTER_RESPONSE" | jq -r '.success' 2>/dev/null)
if [ "$REGISTER_STATUS" = "true" ]; then
    echo -e "${GREEN}✅ REGISTER: OK${NC}\n"
else
    echo -e "${RED}❌ REGISTER: FAILED${NC}\n"
fi

# Login
echo -e "${BLUE}[TEST] POST /api/auth/login${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$REGISTER_EMAIL\",
    \"password\": \"$REGISTER_PASSWORD\"
  }")

echo "$LOGIN_RESPONSE" | jq '.' 2>/dev/null || echo "$LOGIN_RESPONSE"
AUTH_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.access_token' 2>/dev/null)
LOGIN_STATUS=$(echo "$LOGIN_RESPONSE" | jq -r '.success' 2>/dev/null)

if [ "$LOGIN_STATUS" = "true" ] && [ ! -z "$AUTH_TOKEN" ] && [ "$AUTH_TOKEN" != "null" ]; then
    echo -e "${GREEN}✅ LOGIN: OK (Token obtenu)${NC}\n"
else
    echo -e "${RED}❌ LOGIN: FAILED${NC}\n"
    AUTH_TOKEN="fake_token_for_testing"
fi

# Me (GET auth profile)
echo -e "${BLUE}[TEST] GET /api/auth/me (avec token)${NC}"
ME_RESPONSE=$(curl -s -X GET "$API_URL/auth/me" \
  -H "Authorization: Bearer $AUTH_TOKEN")

echo "$ME_RESPONSE" | jq '.' 2>/dev/null || echo "$ME_RESPONSE"
ME_STATUS=$(echo "$ME_RESPONSE" | jq -r '.success' 2>/dev/null)
if [ "$ME_STATUS" = "true" ]; then
    echo -e "${GREEN}✅ ME: OK${NC}\n"
else
    echo -e "${RED}❌ ME: FAILED${NC}\n"
fi

# ============================================================================
# 2. TEST PRODUITS (public)
# ============================================================================
echo -e "${YELLOW}2️⃣  TEST PRODUITS (PUBLIC)${NC}"
echo "-----------------------------------"

# Get all products
echo -e "${BLUE}[TEST] GET /api/products${NC}"
PRODUCTS_RESPONSE=$(curl -s -X GET "$API_URL/products")
echo "$PRODUCTS_RESPONSE" | jq '.' 2>/dev/null | head -30 || echo "$PRODUCTS_RESPONSE" | head -30
PRODUCTS_STATUS=$(echo "$PRODUCTS_RESPONSE" | jq -r '.success' 2>/dev/null)
if [ "$PRODUCTS_STATUS" = "true" ]; then
    echo -e "${GREEN}✅ GET PRODUCTS: OK${NC}\n"
    FIRST_PRODUCT_ID=$(echo "$PRODUCTS_RESPONSE" | jq -r '.data.data[0].id // empty' 2>/dev/null)
else
    echo -e "${YELLOW}⚠️  GET PRODUCTS: Pas de produits ou erreur${NC}\n"
    FIRST_PRODUCT_ID="1"
fi

# Get single product
echo -e "${BLUE}[TEST] GET /api/products/{id}${NC}"
SINGLE_PRODUCT=$(curl -s -X GET "$API_URL/products/$FIRST_PRODUCT_ID")
echo "$SINGLE_PRODUCT" | jq '.' 2>/dev/null | head -20 || echo "$SINGLE_PRODUCT" | head -20
SINGLE_STATUS=$(echo "$SINGLE_PRODUCT" | jq -r '.success' 2>/dev/null)
if [ "$SINGLE_STATUS" = "true" ] || echo "$SINGLE_PRODUCT" | jq -e '.id' >/dev/null 2>&1; then
    echo -e "${GREEN}✅ GET SINGLE PRODUCT: OK${NC}\n"
else
    echo -e "${YELLOW}⚠️  GET SINGLE PRODUCT: Pas de produit avec cet ID${NC}\n"
fi

# ============================================================================
# 3. TEST CATÉGORIES (public)
# ============================================================================
echo -e "${YELLOW}3️⃣  TEST CATÉGORIES (PUBLIC)${NC}"
echo "-----------------------------------"

# Get all categories
echo -e "${BLUE}[TEST] GET /api/categories${NC}"
CATEGORIES_RESPONSE=$(curl -s -X GET "$API_URL/categories")
echo "$CATEGORIES_RESPONSE" | jq '.' 2>/dev/null | head -30 || echo "$CATEGORIES_RESPONSE" | head -30
CATEGORIES_STATUS=$(echo "$CATEGORIES_RESPONSE" | jq -r '.success' 2>/dev/null)
if [ "$CATEGORIES_STATUS" = "true" ]; then
    echo -e "${GREEN}✅ GET CATEGORIES: OK${NC}\n"
else
    echo -e "${YELLOW}⚠️  GET CATEGORIES: Pas de catégories ou erreur${NC}\n"
fi

# ============================================================================
# 4. TEST PANIER (authentifié)
# ============================================================================
echo -e "${YELLOW}4️⃣  TEST PANIER (AUTHENTIFIÉ)${NC}"
echo "-----------------------------------"

# Get cart
echo -e "${BLUE}[TEST] GET /api/cart${NC}"
CART_RESPONSE=$(curl -s -X GET "$API_URL/cart" \
  -H "Authorization: Bearer $AUTH_TOKEN")
echo "$CART_RESPONSE" | jq '.' 2>/dev/null | head -30 || echo "$CART_RESPONSE" | head -30
CART_STATUS=$(echo "$CART_RESPONSE" | jq -r '.success' 2>/dev/null)
if [ "$CART_STATUS" = "true" ]; then
    echo -e "${GREEN}✅ GET CART: OK${NC}\n"
else
    echo -e "${RED}❌ GET CART: FAILED${NC}\n"
fi

# Add to cart (si un produit existe)
if [ ! -z "$FIRST_PRODUCT_ID" ] && [ "$FIRST_PRODUCT_ID" != "null" ]; then
    echo -e "${BLUE}[TEST] POST /api/cart (Ajouter au panier)${NC}"
    ADD_CART_RESPONSE=$(curl -s -X POST "$API_URL/cart" \
      -H "Authorization: Bearer $AUTH_TOKEN" \
      -H "Content-Type: application/json" \
      -d "{
        \"product_id\": $FIRST_PRODUCT_ID,
        \"quantity\": 1
      }")
    echo "$ADD_CART_RESPONSE" | jq '.' 2>/dev/null || echo "$ADD_CART_RESPONSE"
    ADD_CART_STATUS=$(echo "$ADD_CART_RESPONSE" | jq -r '.success' 2>/dev/null)
    if [ "$ADD_CART_STATUS" = "true" ]; then
        echo -e "${GREEN}✅ ADD TO CART: OK${NC}\n"
    else
        echo -e "${YELLOW}⚠️  ADD TO CART: Produit non trouvé ou erreur${NC}\n"
    fi
fi

# ============================================================================
# 5. TEST COMMANDES (authentifié)
# ============================================================================
echo -e "${YELLOW}5️⃣  TEST COMMANDES (AUTHENTIFIÉ)${NC}"
echo "-----------------------------------"

# Get orders
echo -e "${BLUE}[TEST] GET /api/orders${NC}"
ORDERS_RESPONSE=$(curl -s -X GET "$API_URL/orders" \
  -H "Authorization: Bearer $AUTH_TOKEN")
echo "$ORDERS_RESPONSE" | jq '.' 2>/dev/null | head -30 || echo "$ORDERS_RESPONSE" | head -30
ORDERS_STATUS=$(echo "$ORDERS_RESPONSE" | jq -r '.success' 2>/dev/null)
if [ "$ORDERS_STATUS" = "true" ]; then
    echo -e "${GREEN}✅ GET ORDERS: OK${NC}\n"
else
    echo -e "${RED}❌ GET ORDERS: FAILED${NC}\n"
fi

# ============================================================================
# RÉSUMÉ
# ============================================================================
echo -e "${BLUE}===============================================${NC}"
echo -e "${BLUE}     RÉSUMÉ DES TESTS${NC}"
echo -e "${BLUE}===============================================${NC}"
echo -e "${GREEN}✅ Endpoints testés:${NC}"
echo "  • POST /api/auth/register"
echo "  • POST /api/auth/login"
echo "  • GET /api/auth/me"
echo "  • GET /api/products"
echo "  • GET /api/products/{id}"
echo "  • GET /api/categories"
echo "  • GET /api/cart"
echo "  • POST /api/cart"
echo "  • GET /api/orders"
echo ""
echo -e "${BLUE}Pour plus de détails, vérifiez les réponses ci-dessus.${NC}"
echo -e "${BLUE}===============================================${NC}\n"
