import time

class GameStore:
    def __init__(self):
        self.products = {
            "PUBG_UC": 10.0,
            "FreeFire_Diamonds": 5.0,
            "Fortnite_VBucks": 20.0,
            "CallOfDuty_CP": 15.0
        }
        self.inventory = {
            "PUBG_UC": 100,
            "FreeFire_Diamonds": 100,
            "Fortnite_VBucks": 100,
            "CallOfDuty_CP": 100
        }

    def get_product_price(self, product_name):
        return self.products.get(product_name)

    def purchase(self, product_name, quantity, discount_percent=0):
        if product_name not in self.products:
            return "Product not found"
        
        if quantity <= 0:
            return "Invalid quantity"
        
        if self.inventory[product_name] < quantity:
            return "Not enough inventory"

        price = self.get_product_price(product_name)
        
        total_price = price * (1 - discount_percent / 100) * quantity
        
        self.inventory[product_name] -= quantity
        return f"Purchased {quantity} {product_name} for ${total_price:.2f}"

if __name__ == "__main__":
    store = GameStore()
    # Test Case 1: Discount calculation
    # Price 10.0, Qty 5, Discount 10% -> 10 * 0.9 * 5 = 45.0
    print(store.purchase("PUBG_UC", 5, 10)) 
    
    # Test Case 2: Negative quantity (Security fix)
    print(store.purchase("FreeFire_Diamonds", -5))
    
    # Test Case 3: Performance (Speed check not explicitly printed but implicit in execution)
    start_time = time.time()
    store.purchase("Fortnite_VBucks", 1)
    print(f"Execution time: {time.time() - start_time:.4f}s")
