import type CartItem from '../interfaces/carts';

export const getUserCart = async (username: string): Promise<CartItem[]> => {
    try {
        console.log("Username getUserCart: ", username);
        const response = await fetch(`http://localhost:8080/users/${username}/showcart`, {
            headers: {
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error("Failed to fetch user cart");
        }

        const data = await response.json();
        console.log("Data from getUserCart: ", data); // Log the response data
        if (!data || data.cartItems.length === 0) {
            throw new Error("No cart items were fetched from the server");
        }

        return data.cartItems;
    } catch (error) {
        console.error("Failed to fetch user cart items from the server: ", error);
        return [];
    }
}


export const createCartByUser = async (username: string): Promise<any> => {
    try {
        const response = await fetch(`http://localhost:8080/users/${username}/shopping_cart`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error("Failed to create shopping cart");
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Failed to create shopping cart: ", error);
        return null;
    }
}

export const addItemToShoppingCart = async (username: string, productId: string): Promise<any> => {
    try {
        const response = await fetch(`http://localhost:8080/users/${username}/shopping_cart/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error("Failed to add item to shopping cart");
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Failed to add item to shopping cart: ", error);
        return null;
    }
}

export const deleteItemFromShoppingCart = async (username: string, productId: string): Promise<any> => {
    try {
        const response = await fetch(`http://localhost:8080/users/${username}/shopping_cart/${productId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error("Failed to delete item from shopping cart");
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Failed to delete item from shopping cart: ", error);
        return null;
    }
}
